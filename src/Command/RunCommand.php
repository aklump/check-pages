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
      ->addOption('show-request-headers', NULL, InputOption::VALUE_NONE, 'Display request headers.')
      ->addOption('show-request', NULL, InputOption::VALUE_NONE, 'Display request body.')

      // Using "I" to match the curl binary.
      ->addOption('show-headers', 'I', InputOption::VALUE_NONE, 'Display response headers.')
      ->addOption('show-response', NULL, InputOption::VALUE_NONE, 'Display response body.')
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
      echo '🚩 ' . Color::wrap('light gray', $timer->getCurrent()) . PHP_EOL;

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
        $runner->addResolveDirectory($runner_dir)->setPathToSuites($runner_dir);
      }

      $dir = $input->getOption('dir');
      if ($dir) {
        $dir = realpath($dir);
        if (!is_dir($dir)) {
          throw new \InvalidArgumentException("\"$dir\" must be an existing directory.");
        }

        // This path to suites needs to overwrite that from runner directory above.
        $runner->addResolveDirectory($dir)->setPathToSuites($dir);
      }

      if (!$runner->getRunnerPath()) {
        throw new \InvalidArgumentException('You must pass a resolvable path to your PHP runner file, as the only argument, e.g., ./check_pages runner.php');
      }

      require_once ROOT . '/includes/runner_functions.inc';

      if ($input->getOption('filter')) {
        $filters = array_filter($input->getOption('filter'));
        foreach ($filters as $filter) {
          $runner->addSuiteFilter($filter);
        }
      }
      if ($input->getOption('group')) {
        $groups = array_filter($input->getOption('group'));
        foreach ($groups as $filter) {
          $runner->addGroupFilter($filter);
        }
      }

      $runner->executeRunner();

      echo PHP_EOL;
      echo '🏁 ' . Color::wrap('light gray', $timer->getCurrent()) . PHP_EOL;

      echo Color::wrap('white on green', sprintf('Testing completed successfully in %s.', $timer->getElapsed())) . PHP_EOL . PHP_EOL;

      return Command::SUCCESS;
    }
    catch (\Exception $exception) {

      if (isset($runner) && $runner->getOutputMode() === Runner::OUTPUT_DEBUG) {
        $runner->echoMessages();
        echo PHP_EOL;
      }

      echo PHP_EOL;
      echo '🏁 ' . Color::wrap('light gray', $timer->getCurrent()) . PHP_EOL;

      // Shift the first line so only that is red, in case we want to dump a
      // pretty-print JSON variable.
      $lines = explode(PHP_EOL, $exception->getMessage());
      echo Color::wrap('white on red', array_shift($lines)) . PHP_EOL;
      echo implode(PHP_EOL, $lines) . PHP_EOL . PHP_EOL;

      return Command::FAILURE;
    }
  }
}
