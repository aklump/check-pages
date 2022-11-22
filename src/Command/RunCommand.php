<?php

namespace AKlump\CheckPages\Command;

use AKlump\CheckPages\CheckPages;
use AKlump\CheckPages\Output\ConsoleEchoPrinter;
use AKlump\CheckPages\Output\Message;
use AKlump\Messaging\MessageType;
use AKlump\Messaging\MessengerInterface;
use AKlump\CheckPages\Output\Timer;
use AKlump\CheckPages\Output\VerboseDirective;
use AKlump\CheckPages\Parts\Runner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends Command {

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
      ->addOption('retest', NULL, InputOption::VALUE_NONE, 'Execute runner skipping any suite that previously passed, retested only the failed suites.')
      ->addOption('continue', NULL, InputOption::VALUE_NONE, 'Execute runner beginning with the most recently executed suite, inclusive.');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $runner = new Runner(ROOT, $input, $output);
    $messenger = $runner->getMessenger();

    try {
      global $container;

      $container->set('input', $input);
      $container->set('output', $output);

      // Pull the timezone from the system running this.
      $timezone = new \DateTimeZone(exec('date +%Z'));
      $timer = new Timer($timezone);
      $timer->start();
      $this->echoTimer($messenger, $timer);

      $container->set('runner', $runner);
      $path_to_runner = $input->getArgument('runner');
      $runner->setBasename(basename($path_to_runner));

      // Add the directory of the runner as a resolvable directory and path to suites.
      if ($path_to_runner) {
        $realpath_to_runner = realpath($path_to_runner);
        if (!$realpath_to_runner) {
          throw new \InvalidArgumentException("The runner file: \"$path_to_runner\" does not exist.");
        }
        $runner_dir = dirname($realpath_to_runner);
        $runner->addResolveDirectory($runner_dir);
      }

      $dir = $input->getOption('dir');
      if ($dir) {
        $resolved_dir = realpath($dir);
        if (!is_dir($resolved_dir)) {
          throw new \InvalidArgumentException("\"$dir\" must be an existing directory.");
        }

        // This path to suites needs to overwrite that from runner directory above.
        $runner->addResolveDirectory($resolved_dir);
      }

      if (!$runner->getRunnerPath()) {
        throw new \InvalidArgumentException('You must pass a resolvable path to your PHP runner file, as the only argument, e.g., ./check_pages runner.php');
      }

      require_once ROOT . '/includes/runner_functions.inc';

      if ($input->getOption('filter')) {
        $filters = array_filter($input->getOption('filter'));
        foreach ($filters as $filter) {
          $runner->addSuiteIdFilter($filter);
        }
      }
      if ($input->getOption('group')) {
        $groups = array_filter($input->getOption('group'));
        foreach ($groups as $filter) {
          $runner->addSuiteGroupFilter($filter);
        }
      }

      $runner->executeRunner();

      $this->echoResults($runner, $timer);

      return Command::SUCCESS;
    }
    catch (\Exception $exception) {
      $message = trim($runner->getMessageOutput());
      if ($message) {
        $messenger->deliver(new Message(
          [
            $message,
            '',
          ],
          MessageType::ERROR
        ));
      }

      $this->echoTimer($messenger, $timer);

      // Shift the first line so only that is red, in case we want to dump a
      // pretty-print JSON variable.
      $message = trim($exception->getMessage());
      if ($message) {
        $lines = explode(PHP_EOL, $message);
        $first_line = array_shift($lines);
        $messenger->deliver(new Message([
          $first_line,
          '',
        ], MessageType::ERROR), ConsoleEchoPrinter::INVERT);

        $messenger->deliver(new Message($lines, MessageType::ERROR));
        $messenger->deliver(new Message(['', '']));
      }

      $this->echoResults($runner, $timer, $exception);

      return Command::FAILURE;
    }
  }

  private function echoResults(Runner $runner, Timer $timer, \Exception $exception = NULL) {
    $messenger = $runner->getMessenger();
    $this->echoTimer($messenger, $timer);

    $total_test_count = $runner->getTotalTestsRun();

    //    if (!$exception && 0 === $total_test_count) {
    //      echo Color::wrap('yellow', 'No tests were run.');
    //      echo $footer;
    //
    //      return;
    //    }

    // Percentage
    $percentage = NULL;
    if (!$exception) {
      $passed_test_count = $runner->getTotalPassingTestsRun();
      $percentage = $total_test_count ? intval(100 * $passed_test_count / $total_test_count) : 100;
      $message = new Message([
          sprintf("%d / %d (%d%%)", $passed_test_count, $total_test_count, $percentage),
          '',
          '',
        ]
      );
      $messenger->deliver($message);
    }

    $this->echoTimer($messenger, $timer);

    // TODO Is this redundant?
    // Time
    $message = new Message([
        sprintf("Time: %s", $timer->getElapsed()),
        '',
        '',
      ]
    );
    $messenger->deliver($message);

    $total_assertion_count = $runner->getTotalAssertionsRun();
    $ok = !$exception || 100 === $percentage;
    if ($ok) {
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
      $messenger->deliver($message, ConsoleEchoPrinter::INVERT_FIRST);

    }
    else {
      // Sometimes a test fails without an assertion failing, e.g. the HTTP response code.
      $failed_count = max($runner->getTotalFailedTests(), $runner->getTotalFailedAssertions(), intval(isset($exception)));
      $message = new Message(
        [
          'FAILURES!',
          '',
          sprintf('Tests: %d, Assertions: %d, Failures: %d',
            $total_test_count,
            $total_assertion_count,
            $failed_count
          ),
          '',
        ],
        MessageType::ERROR
      );
      $messenger->deliver($message, ConsoleEchoPrinter::INVERT_FIRST);
    }
  }

  private function echoTimer(MessengerInterface $messenger, Timer $timer) {
    $message = new Message(
      [
        '',
        '🏁 ' . $timer->getCurrent(),
      ],
      MessageType::DEBUG,
      new VerboseDirective('V')
    );
    $messenger->deliver($message);
  }
}
