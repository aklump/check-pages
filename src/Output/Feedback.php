<?php

namespace AKlump\CheckPages\Output;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\TestEvent;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Parts\Test;
use AKlump\LoftLib\Bash\Color;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides user feedback based on output verbosity.
 */
class Feedback implements EventSubscriberInterface {

  const COLOR_PENDING = 'purple';

  const COLOR_PENDING_BG = 'magenta';

  /**
   * @var \Symfony\Component\Console\Output\OutputInterface
   */
  public static $suiteTitle;

  /**
   * @var \Symfony\Component\Console\Output\OutputInterface
   */
  public static $requestUrl;

  /**
   * @var \Symfony\Component\Console\Output\OutputInterface
   */
  public static $requestHeaders;

  /**
   * @var \Symfony\Component\Console\Output\OutputInterface
   */
  public static $requestBody;

  /**
   * @var \Symfony\Component\Console\Output\OutputInterface
   */
  public static $responseHeaders;

  /**
   * @var \Symfony\Component\Console\Output\OutputInterface
   */
  public static $responseBody;

  /**
   * @var \Symfony\Component\Console\Output\OutputInterface
   */
  public static $testTitle;

  /**
   * @var \Symfony\Component\Console\Output\OutputInterface
   */
  public static $testDetails;

  /**
   * @var \Symfony\Component\Console\Output\OutputInterface
   */
  public static $testResult;

  public static function shouldRespond(Test $test): bool {
    return $test->getRunner()
      ->getOutput()
      ->isVerbose();
  }

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents() {
    return [
      Event::TEST_CREATED => [
        function (TestEventInterface $event) {
          if (!self::shouldRespond($event->getTest())) {
            return;
          }

          $test = $event->getTest();
          $config = $test->getConfig();

          // TODO Move this to the javascript plugin.
          if ($config['js'] ?? FALSE) {
            $test->addBadge('☕');
          }
          self::updateTestStatus($test->getDescription());
        },
      ],

      Event::TEST_FINISHED => [
        function (Event\DriverEventInterface $event) {
          $test = $event->getTest();
          if (self::shouldRespond($test) || $test->hasFailed()) {
            self::updateTestStatus($test->getDescription(), $test->hasPassed());
          }
          if (!self::shouldRespond($event->getTest())) {
            return;
          }

          // Create the failure output files.
          // TODO Move this to another place.
          if ($test->hasFailed()) {

            if (!empty($url)) {
              $failure_log = [$url];
            }

            $runner = $test->getRunner();
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

          $runner = $event->getTest()->getRunner();
          if ($runner->getMessages()) {
            self::$testDetails->write($runner->getMessageOutput(), OutputInterface::VERBOSITY_VERY_VERBOSE);
          }
        },
      ],
    ];
  }

  /**
   * Displays $title in the proper format/color for the suite.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   * @param string $title
   *   Do not include emojis, they are added for you.
   * @param null|bool $status
   *   Boolean if the suite is finished and the final result.  True means all
   *   tests passed.
   *
   * @return void
   */
  public static function updateSuiteTitle(OutputInterface $output, string $title, $status = NULL) {
    $compact_mode = !$output->isVerbose() && !$output->isQuiet();

    // In compact mode, no test details should display.
    if ($compact_mode) {
      if (TRUE === $status) {
        Feedback::$suiteTitle->overwrite(['👍 ' . Color::wrap('green', $title)]);
      }
      elseif (FALSE === $status) {
        Feedback::$suiteTitle->overwrite(['🚫 ' . Color::wrap('white on red', $title)]);
      }
      else {
        Feedback::$suiteTitle->overwrite(['🔎 ' . Color::wrap(Feedback::COLOR_PENDING, $title)]);
      }
    }

    // In the following mode, the test details should also display.
    else {
      $title = '    ' . strtoupper($title) . ' ';
      if (TRUE === $status) {
        Feedback::$suiteTitle->overwrite([
          Color::wrap('white on green', $title),
          '',
        ]);
      }
      elseif (FALSE === $status) {
        Feedback::$suiteTitle->overwrite([
          Color::wrap('white on red', $title),
          '',
        ]);
      }
      else {
        Feedback::$suiteTitle->overwrite([
          Color::wrap('white on ' . Feedback::COLOR_PENDING_BG, $title),
          '',
        ]);
      }
    }
  }

  public static function updateTestStatus(string $title, $status = NULL, $icon = NULL) {
    if (TRUE === $status) {
      self::$testTitle->overwrite(($icon ?? '👍  ') . Color::wrap('green', $title));
      self::$testResult->overwrite([Color::wrap('green', '└── Passed.'), '']);
    }
    elseif (FALSE === $status) {
      self::$testTitle->overwrite(($icon ?? '🚫  ') . Color::wrap('white on red', $title));
      self::$testResult->overwrite([Color::wrap('red', '└── Failed.'), '']);
    }
    else {
      self::$testTitle->overwrite(($icon ?? '🔎  ') . Color::wrap(Feedback::COLOR_PENDING, $title));
      self::$testResult->overwrite([
        Color::wrap(Feedback::COLOR_PENDING, '└── Pending...'),
        '',
      ]);
    }
  }

}
