<?php

namespace AKlump\CheckPages\Command;

use AKlump\CheckPages\Exceptions\UnresolvablePathException;
use AKlump\CheckPages\Files\FilesProviderInterface;
use AKlump\CheckPages\Handlers\Breakpoint;
use AKlump\CheckPages\Output\Feedback;
use AKlump\CheckPages\Output\Flags;
use AKlump\CheckPages\Output\Message;
use AKlump\CheckPages\Output\Timer;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Service\Retest;
use AKlump\Messaging\MessageType;
use AKlump\Messaging\MessengerInterface;
use AKlump\LocalTimezone\LocalTimezone;
use Exception;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends Command {

  /**
   * @var \AKlump\CheckPages\Files\FilesProviderInterface
   */
  private $rootFiles;

  public function __construct(FilesProviderInterface $root_files, string $name = NULL) {
    parent::__construct($name);
    $this->rootFiles = $root_files;
  }

  protected static $defaultName = 'run';

  protected function configure() {
    $this
      ->setDescription('Execute a runner.')
      //      ->setHelp('Execute a runner file.')
      ->addArgument('runner', InputArgument::REQUIRED, 'The path to the runner file.')
      ->addOption('dir', NULL, InputOption::VALUE_REQUIRED, 'An existing directory where the suite files are located.')
      ->addOption('config', NULL, InputOption::VALUE_REQUIRED, 'The filename of the configuration to use; extension not required.')
      ->addOption('show', '', InputOption::VALUE_REQUIRED, "Show details about the request/response cycle.\nAny combination of the following characters:\nS - send/request body\nR - response body\nH - used alone all headers; HS for send w/headers; HR for response w/headers\nA - all of the above; same as HSR\n")
      ->addOption('truncate', NULL, InputOption::VALUE_REQUIRED, 'Max characters to display in headers and bodies. Set to 0 for no limit.', 768)
      ->addOption('filter', 'f', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, "Define a subset by id(s).  Suite ids must match at least one value or they will be skipped.\nMay be combined with the group filter.")
      ->addOption('group', 'g', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Define a subset by group(s).  Suite groups must match at least one value or they will be skipped.')
      ->addOption(Retest::OPTION_RETEST, NULL, InputOption::VALUE_NONE, 'Execute runner skipping any suite that previously passed, retested only the failed suites.')
      ->addOption(Retest::OPTION_CONTINUE, NULL, InputOption::VALUE_NONE, 'Execute runner beginning with the most recently executed suite, inclusive.')
      ->addOption(Breakpoint::OPTION_BREAK, NULL, InputOption::VALUE_NONE, 'Stop execution at breakpoints until users presses a key.');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    global $container;

    $timer = new Timer(LocalTimezone::get());
    $timer->start();

    // Keep outside of try, because the catch needs this instance.
    $runner = new Runner($input, $output);

    try {

      // Now that we have a runner we pass it to the plugins manager to the
      // dispatcher can be connected to the handlers.

      /** @var \AKlump\CheckPages\Plugin\HandlersManager $plugins_manager */
      $plugins_manager = $container->get('handlers_manager');
      $plugins_manager->setRunner($runner);

      $messenger = $runner->getMessenger();

      // We set this now because there were not available before this point.  We
      // set them so that runner functions may use them.
      $container->set('input', $input);
      $container->set('output', $output);

      $this->echoTimer($messenger, $timer);

      $container->set('runner', $runner);
      $path_to_runner = $input->getArgument('runner');
      if (!file_exists($path_to_runner)) {
        throw new UnresolvablePathException($path_to_runner);
      }
      $runner->setId(pathinfo($path_to_runner, PATHINFO_FILENAME));

      $this->rootFiles->setBaseResolveDir(realpath(dirname($path_to_runner)));
      $runner->setFiles($this->rootFiles);

      $dir = $input->getOption('dir');
      if ($dir) {
        if (!is_dir($dir)) {
          throw new InvalidArgumentException("\"$dir\" must be an existing directory.");
        }

        // This path to suites needs to overwrite that from runner directory above.
        $runner->getFiles()->addResolveDir(realpath($dir));
      }

      if ($input->getOption('filter')) {
        $filters = array_filter($input->getOption('filter'));
        foreach ($filters as $filter) {
          $runner->addFilter($filter);
        }
      }

      // TODO Deprecate this.
      if ($input->getOption('group')) {
        $output->writeln('<error>--group is deprecated, use the format "--filter={group}/" to filter by group.');
        $groups = array_filter($input->getOption('group'));
        foreach ($groups as $filter) {
          $runner->addFilter("$filter/");
        }
      }

      $runner->executeRunner($path_to_runner);
    }
    catch (Exception $exception) {
      $runner->setFailed();

      //      $suite = $runner->getSuite();
      //      if ($suite) {
      //        $runner->getDispatcher()
      //          ->dispatch(new SuiteEvent($suite), Event::SUITE_FAILED);
      //      }

      // Convert the exception to messages.
      $message = trim($exception->getMessage());
      if ($message) {
        // Message as an error message.
        $lines = explode(PHP_EOL, $message);
        $runner->addMessage(new Message($lines, MessageType::ERROR));
        // Trace as a debug message.
        $lines = explode(PHP_EOL, $exception->getTraceAsString());
        foreach ($lines as $line) {
          $runner->addMessage(new Message([$line], MessageType::ERROR, Verbosity::DEBUG));
        }
      }
    }

    $this->echoResults($runner, $timer);

    if ($runner->hasPassed()) {
      return Command::SUCCESS;
    }

    return Command::FAILURE;
  }

  private function echoResults(Runner $runner, Timer $timer) {
    $messenger = $runner->getMessenger();
    $this->echoTimer($messenger, $timer);

    foreach ($runner->getMessages() as $message) {
      $messenger->deliver($message);
    }

    $total_test_count = $runner->getTotalTestsRun();

    // Percentage
    $percentage = NULL;
    if ($runner->hasPassed()) {
      $passed_test_count = $runner->getTotalPassingTestsRun();
      $percentage = $total_test_count ? intval(100 * $passed_test_count / $total_test_count) : 100;
      $message = new Message([
          sprintf("%d / %d (%d%%)", $passed_test_count, $total_test_count, $percentage),
        ]
      );
      $messenger->deliver($message);
    }

    // Calculated, total elapsed time.
    $messenger->deliver(new Message(
      [
        sprintf("Time: %s", $timer->getElapsed()),
        '',
      ]
    ));

    if ($runner->getTotalSkippedSuites() > 0) {
      $message = new Message([
        Feedback::SKIPPED_PREFIX . "At least one suite was skipped.",
        '',
      ], 'skipped');
      $messenger->deliver($message);
    }

    $total_assertion_count = $runner->getTotalAssertionsRun();
    $ok = $runner->hasPassed() || 100 === $percentage;
    $flags = Flags::INVERT_FIRST_LINE;
    if ($total_test_count > 0 && $ok) {
      $message = new Message([
        sprintf("OK (%d test%s, %d assertion%s)",
          $total_test_count,
          $total_test_count === 1 ? '' : 's',
          $total_assertion_count,
          $total_assertion_count === 1 ? '' : 's'
        ),
        '',
      ],
        MessageType::SUCCESS
      );
    }
    else {
      $last_failed_suite = $runner->getLastFailedSuite();
      if ($last_failed_suite) {
        $title = sprintf("Suite: %s", $last_failed_suite);
        $margin = str_repeat(' ', 27);
        $message = new Message([
          "$margin$title$margin",
          '',
        ], MessageType::ERROR);
        $messenger->deliver($message, Flags::INVERT_FIRST_LINE);
      }

      // Sometimes a test fails without an assertion failing, e.g. the HTTP response code.
      $failed_count = max($runner->getTotalFailedTests(), $runner->getTotalFailedAssertions(), intval($runner->hasFailed()));
      $failed_count = min($failed_count, $total_test_count);
      $lines = [
        '',
        sprintf('Tests: %d, Assertions: %d, Failures: %d',
          $total_test_count,
          $total_assertion_count,
          $failed_count
        ),
        '',
      ];

      if ($failed_count > 0 && $total_test_count > 0) {
        array_unshift($lines, 'FAILURES!');
      }
      else {
        $flags = NULL;
      }

      $message_type = $total_test_count > 0 ? MessageType::ERROR : MessageType::INFO;
      $message = new Message($lines, $message_type);
    }
    $messenger->deliver($message, $flags);
  }

  private function echoTimer(MessengerInterface $messenger, Timer $timer) {
    $message = new Message(
      [
        '',
        '🏁 ' . $timer->getCurrent(),
      ],
      MessageType::DEBUG,
      Verbosity::VERBOSE
    );
    $messenger->deliver($message);
  }
}
