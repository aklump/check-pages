<?php

namespace AKlump\CheckPages\Output;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\DriverEventInterface;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Parts\Runner;
use AKlump\LoftLib\Bash\Color;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides user feedback based on output verbosity.
 */
class Feedback implements EventSubscriberInterface {

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents() {
    return [
      Event::TEST_CREATED => [
        function (TestEventInterface $event) {
          $test = $event->getTest();
          $runner = $test->getRunner();
          $output = $runner->getOutput();

          if (!empty($test->getConfig()['why'])) {
            $level = OutputInterface::VERBOSITY_VERBOSE;
            $output->writeln(Color::wrap('green', '├── ' . $test->getConfig()['why']), $level);
          }
        },
      ],
      Event::REQUEST_CREATED => [self::class, 'testCreated'],
      Event::TEST_FINISHED => [self::class, 'testFinished'],
    ];
  }

  /**
   * Write output before the test has begun.
   *
   * @param \AKlump\CheckPages\Event\DriverEventInterface $event
   *
   * @return void
   */
  public static function testCreated(TestEventInterface $event) {
    $test = $event->getTest();
    $runner = $test->getRunner();
    $output = $runner->getOutput();
    $is_quiet = $output->getVerbosity() === OutputInterface::VERBOSITY_QUIET;
    $config = $test->getConfig();

    // The feedback provided by this method is only for URL-based tests.
    if ($is_quiet || empty($config['url'])) {
      return;
    }
    $is_verbose = $output->getVerbosity() === OutputInterface::VERBOSITY_VERBOSE;
    if ($is_verbose) {
      echo Color::wrap('light gray', $test->getHttpMethod() . ' ' . $test->getAbsoluteUrl()) . PHP_EOL;
    }

    echo '🔎 ';
    echo Color::wrap('blue', $test->getDescription()) . ' ';

    if ($config['js'] ?? FALSE) {

      // TODO Change to getOutput()
      if ($runner->getOutputMode() !== Runner::OUTPUT_QUIET) {
        echo "☕";
      }
    }
  }

  /**
   * Write output after the test is finished.
   *
   * @param \AKlump\CheckPages\Event\TestEventInterface $event
   *
   * @return void
   */
  public static function testFinished(TestEventInterface $event) {
    $test = $event->getTest();
    $runner = $test->getRunner();
    $output = $runner->getOutput();
    $is_quiet = $output->getVerbosity() === OutputInterface::VERBOSITY_QUIET;
    if ($is_quiet) {
      return;
    }

    if (!$test->hasFailed()) {
      echo '👍';
    }
    else {

      // When in normal mode, it's hard to see what failed, so we will add a
      // highlighted line here to be seen.  When verbose the assertions are
      // displayed, so it's not an issue there.
      if ($output->getVerbosity() === OutputInterface::VERBOSITY_NORMAL) {
        echo PHP_EOL;
        echo Color::wrap('light gray', $test->getAbsoluteUrl()) . PHP_EOL;
        echo '🚫 ' . Color::wrap('white on red', $test->getDescription());
      }
      else {
        echo '🚫';
      }
    }

    // Create the failure output files.
    if ($test->hasFailed()) {
      if (!empty($url)) {
        $failure_log = [$url];
      }
      foreach ($runner->getMessages() as $item) {
        if ('error' === $item['level']) {
          $failure_log[] = $item['data'];
        }
      }
      $failure_log[] = PHP_EOL;
      $runner->writeToFile('failures', $failure_log);

      $suite = $test->getSuite();
      FailedTestMarkdown::output("{$suite->id()}{$test->id()}", $test);
    }

    //    $is_verbose = $output->getVerbosity() === OutputInterface::VERBOSITY_VERBOSE;
    if ($runner->getMessages()) {
      echo PHP_EOL;
      echo $runner->getMessageOutput();
      echo PHP_EOL;
    }

    echo PHP_EOL;
  }

}
