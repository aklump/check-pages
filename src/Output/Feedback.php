<?php

namespace AKlump\CheckPages\Output;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\DriverEventInterface;
use AKlump\CheckPages\Event\TestEventInterface;
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
      Event::TEST_CREATED => [self::class, 'testCreated'],
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

    echo '🔎 ';
    if (!empty($config['why'])) {
      echo Color::wrap('blue', $config['why']) . ' ';
      if ($is_verbose) {
        echo PHP_EOL;
      }
    }
    if ($is_verbose || empty($config['why'])) {
      $suite = $test->getSuite();
      $has_multiple_methods = count($suite->getHttpMethods()) > 1;
      $method = $has_multiple_methods ? $test->getHttpMethod() : '';
      $url = !$is_verbose ? $config['url'] : $runner->url($config['url']);
      echo Color::wrap('blue', ltrim("$method $url ", ' '));
    }

    if ($config['js'] ?? FALSE) {
      echo "☕ ";
    }
  }

  /**
   * Write output after the test is finished.
   *
   * @param \AKlump\CheckPages\Event\DriverEventInterface $event
   *
   * @return void
   */
  public static function testFinished(DriverEventInterface $event) {
    $test = $event->getTest();
    $runner = $test->getRunner();
    $output = $runner->getOutput();
    $is_quiet = $output->getVerbosity() === OutputInterface::VERBOSITY_QUIET;
    if ($is_quiet) {
      return;
    }

    echo !$test->hasFailed() ? '👍' : '🚫';

    // Create the failure output files.
    if ($test->hasFailed()) {
      if (!empty($url)) {
        $failure_log = [$url];
      }
      foreach ($runner->getDebugArray() as $item) {
        if ('error' === $item['level']) {
          $failure_log[] = $item['data'];
        }
      }
      $failure_log[] = PHP_EOL;
      $runner->writeToFile('failures', $failure_log);

      $suite = $test->getSuite();
      FailedTestMarkdown::output("{$suite->id()}{$test->id()}", $test);
    }

    $is_verbose = $output->getVerbosity() === OutputInterface::VERBOSITY_VERBOSE;
    if ($is_verbose && $runner->getDebugArray()) {
      echo PHP_EOL;
      $runner->echoMessages();
      echo PHP_EOL;
    }

    echo PHP_EOL;
  }
}
