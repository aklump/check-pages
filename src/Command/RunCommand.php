<?php

namespace AKlump\CheckPages\Command;

use AKlump\CheckPages\CheckPages;
use AKlump\CheckPages\Output\Timer;
use AKlump\CheckPages\Parts\Runner;
use AKlump\LoftLib\Bash\Color;
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
      //      ->addOption('quiet', NULL, InputOption::VALUE_NONE, 'Operate in quiet mode.')
      ->addOption('request', NULL, InputOption::VALUE_NONE, 'Display request headers and body.')
      ->addOption('req-headers', NULL, InputOption::VALUE_NONE, 'Display request headers only.')
      ->addOption('req', NULL, InputOption::VALUE_NONE, 'Display request body only.')

      // Using "I" to match the curl binary.
      ->addOption('response', NULL, InputOption::VALUE_NONE, 'Display response headers and body. See --truncate.')
      ->addOption('headers', 'I', InputOption::VALUE_NONE, 'Display response headers.')
      ->addOption('res', NULL, InputOption::VALUE_NONE, 'Display response body. See --truncate.')
      ->addOption('truncate', NULL, InputOption::VALUE_REQUIRED, 'Max characters to display in headers and bodies. Set to 0 for no limit.', 768)
      ->addOption('filter', 'f', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Indicate a subset of one or more suites to run.')
      ->addOption('group', 'g', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Indicate the group(s) to filter by; suites having other groups will be ignored.')
      ->addOption('retest', NULL, InputOption::VALUE_NONE, 'Execute runner skipping any suite that previously passed, retested only the failed suites.')
      ->addOption('continue', NULL, InputOption::VALUE_NONE, 'Execute runner beginning with the most recently executed suite, inclusive.');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    try {
      global $container;

      $container->set('input', $input);
      $container->set('output', $output);

      // Pull the timezone from the system running this.
      $timezone = new \DateTimeZone(exec('date +%Z'));
      $timer = new Timer($timezone);
      $timer->start();
      $output->writeln('🚩 ' . Color::wrap('light gray', $timer->getCurrent()), OutputInterface::VERBOSITY_VERY_VERBOSE);

      $runner = new Runner(ROOT, $input, $output);
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

      $this->writeTimer($output, $timer);
      $this->outputResults($runner, $timer);
      echo PHP_EOL . PHP_EOL;

      return Command::SUCCESS;
    }
    catch (\Exception $exception) {

      if (isset($runner) && $runner->getOutput()->isDebug()) {
        $message = $runner->getMessageOutput();
        if (trim($message)) {
          echo $message;
          echo PHP_EOL;
        }
      }

      $this->writeTimer($output, $timer);

      // Shift the first line so only that is red, in case we want to dump a
      // pretty-print JSON variable.
      $message = trim($exception->getMessage());
      if ($message) {
        $lines = explode(PHP_EOL, $message);
        echo Color::wrap('white on red', array_shift($lines)) . PHP_EOL;
        echo implode(PHP_EOL, $lines) . PHP_EOL . PHP_EOL;
      }

      if (isset($runner)) {
        $this->outputResults($runner, $timer, $exception);
      }

      return Command::FAILURE;
    }
  }

  private function outputResults(Runner $runner, Timer $timer, \Exception $exception = NULL) {
    $footer = PHP_EOL;
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
      echo sprintf("%d / %d (%d%%)", $passed_test_count, $total_test_count, $percentage) . PHP_EOL . PHP_EOL;
    }

    // Time
    echo sprintf("Time: %s", $timer->getElapsed()) . PHP_EOL . PHP_EOL;

    // Message
    $total_assertion_count = $runner->getTotalAssertionsRun();
    $ok = !$exception || 100 === $percentage;
    if ($ok) {
      echo Color::wrap('white on green', sprintf("OK (%d test%s, %d assertion%s)",
        $total_test_count,
        $total_test_count === 1 ? '' : 's',
        $total_assertion_count,
        $total_assertion_count === 1 ? '' : 's'
      ));
      echo $footer;
    }
    else {
      // Sometimes a test fails without an assertion failing, e.g. the HTTP response code.
      $failed_count = max($runner->getTotalFailedTests(), $runner->getTotalFailedAssertions(), intval(isset($exception)));

      echo Color::wrap('white on red', sprintf("FAILURES!\nTests: %d, Assertions: %d, Failures: %d",
        $total_test_count,
        $total_assertion_count,
        $failed_count
      ));
      echo $footer;
    }

  }

  private function writeTimer(OutputInterface $output, Timer $timer) {
    $output->writeln([
      '🏁 ' . Color::wrap('light gray', $timer->getCurrent()),
    ], OutputInterface::VERBOSITY_VERY_VERBOSE);
  }
}
