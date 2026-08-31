<?php

namespace AKlump\CheckPages\Command;

use AKlump\CheckPages\EventSubscriber\Retest;
use AKlump\CheckPages\Exceptions\UnresolvablePathException;
use AKlump\CheckPages\Files\FilesProviderInterface;
use AKlump\CheckPages\Files\GetShortPath;
use AKlump\CheckPages\Handlers\Breakpoint;
use AKlump\CheckPages\Output\Flags;
use AKlump\CheckPages\Output\Message\ExceptionMessage;
use AKlump\CheckPages\Output\Message\Message;
use AKlump\CheckPages\Output\Timer;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\RuntimeContext;
use AKlump\LocalTimezone\LocalTimezone;
use AKlump\CheckPages\Messaging\MessageType;
use AKlump\CheckPages\Messaging\MessengerInterface;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends Command {

  /**
   * Unified TAP point sequence shared by Test and Assert.
   * A test is ALWAYS a TAP test point: it always consumes one number and
   * always appears in TAP output as "ok N - # test: <basename>".
   * A test with 0 assertions uses only its own point number.
   * A test with N assertions uses its own number plus one number per assertion.
   */
  private static int $pointNumberSequence = 0;

  /** Suite sequence; suites are TAP diagnostic comments, not test points. */
  private static int $suiteNumberSequence = 0;

  public static function nextPointNumber(): int {
    return ++self::$pointNumberSequence;
  }

  public static function nextSuiteNumber(): int {
    return ++self::$suiteNumberSequence;
  }

  public static function resetSequences(): void {
    self::$pointNumberSequence = 0;
    self::$suiteNumberSequence = 0;
  }

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

    // Keep outside of try, because the catch needs this instance.
    $runner = new Runner($input, $output);
    RuntimeContext::get()->add($runner);

    $timer = new Timer(LocalTimezone::get());
    $timer->start();
    $runner->setTimer($timer);

    try {

      // Now that we have a runner we pass it to the plugins manager to the
      // dispatcher can be connected to the handlers.

      /** @var \AKlump\CheckPages\Plugin\HandlersManager $plugins_manager */
      $plugins_manager = $container->get('handlers_manager');
      $plugins_manager->setRunner($runner);

      $messenger = $runner->getMessenger();
      RuntimeContext::get()->add($messenger);

      // We set this now because there were not available before this point.  We
      // set them so that runner functions may use them.
      $container->set('input', $input);
      $container->set('output', $output);

      $container->set('runner', $runner);
      $pathToRunnerFile = $input->getArgument('runner');
      $runner->setPathToRunnerFile($pathToRunnerFile);
      if (!file_exists($pathToRunnerFile)) {
        throw new UnresolvablePathException($pathToRunnerFile);
      }
      $runner->setId(pathinfo($pathToRunnerFile, PATHINFO_FILENAME));

      $this->rootFiles->setBaseResolveDir(realpath(dirname($pathToRunnerFile)));
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

      $runner->executeRunner();
    }
    catch (\Exception $exception) {
      $runner->setFailed();

      $message = new ExceptionMessage(
        $exception,
        $runner->getMessenger()->getVerbosity(),
        (string) RuntimeContext::get(),
      );
      $runner->getMessenger()->writeln($message);

      //      $suite = $runner->getSuite();
      //      if ($suite) {
      //        $runner->getDispatcher()
      //          ->dispatch(new SuiteEvent($suite), Event::SUITE_FAILED);
      //      }
      $runner->addMessage(new ExceptionMessage(
        $exception,
        $runner->getMessenger()->getVerbosity(),
        (string) RuntimeContext::get(),
      ));
    }

    if ($runner->hasPassed()) {
      return Command::SUCCESS;
    }

    return Command::FAILURE;
  }

}
