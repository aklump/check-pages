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

  /**
   * @var \Symfony\Component\Console\Output\OutputInterface
   */
  public static $testUrl;

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
        ->getVerbosity() > OutputInterface::VERBOSITY_NORMAL;
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

          //
          // HTTP absolute URL
          //
          if (!empty($config['url'])) {
            self::$testUrl->overwrite('   ' . Color::wrap('light gray', $test->getHttpMethod() . ' ' . $test->getAbsoluteUrl()));
          }

          //
          // Test description/title
          //
          $config = $test->getConfig();
          if (!isset($config['extra']['icons'])) {
            $icons_event = new TestEvent($test);
            $test->getRunner()
              ->getDispatcher()
              ->dispatch($icons_event, Event::TEST_ICONS);
            $config['extra']['icons'] = implode('', $icons_event->getIcons());
            $test->setConfig($config);
          }
          self::updateTestStatus($test->getDescription());
        },
      ],

      Event::TEST_ICONS => [
        function (TestEventInterface $event) {
          if (self::shouldRespond($event->getTest())) {

            // TODO Move this to the javascript plugin.
            if ($event->getTest()->getConfig()['js'] ?? FALSE) {
              $event->addIcon('☕');
            }
          }
        },
      ],

      Event::TEST_FINISHED => [
        function (TestEventInterface $event) {
          if (!self::shouldRespond($event->getTest())) {
            return;
          }

          $test = $event->getTest();
          self::updateTestStatus($test->getDescription(), $test->hasPassed());
          if ($test->hasFailed()) {
            self::$testUrl->overwrite('   ' . Color::wrap('red', $test->getHttpMethod() . ' ' . $test->getAbsoluteUrl()));
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

  public static function updateTestStatus(string $title, $status = NULL, $icon = NULL) {
    if (TRUE === $status) {
      self::$testTitle->overwrite(($icon ?? '👍 ') . Color::wrap('green', $title));
      self::$testResult->overwrite([Color::wrap('green', '└── Passed.'), '']);
    }
    elseif (FALSE === $status) {
      self::$testTitle->overwrite(($icon ?? '🚫 ') . Color::wrap('white on red', $title));
      self::$testResult->overwrite([Color::wrap('red', '└── Failed.'), '']);
    }
    else {
      self::$testTitle->overwrite(($icon ?? '🔎 ') . Color::wrap(\AKlump\CheckPages\Output\Feedback::COLOR_PENDING, $title));
      self::$testResult->overwrite([Color::wrap(\AKlump\CheckPages\Output\Feedback::COLOR_PENDING, '└── Pending...'), '']);
    }
  }

}
