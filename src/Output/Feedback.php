<?php

namespace AKlump\CheckPages\Output;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Parts\Runner;
use AKlump\LoftLib\Bash\Color;
use AKlump\Messaging\MessageInterface;
use AKlump\Messaging\MessageType;
use AKlump\Messaging\MessengerInterface;
use AKlump\Messaging\Processors\Messenger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides user feedback based on output verbosity.
 */
class Feedback implements EventSubscriberInterface {

  /**
   * This must be a background color.
   *
   * @var string
   */
  const FAILED_PREFIX = '🚫 FAILED: ';

  const COLOR_PENDING = 'purple';

  const COLOR_PENDING_BG = 'magenta';

  /**
   * @var \Symfony\Component\Console\Output\ConsoleSectionOutput
   */
  public static $testTitle;

  /**
   * @var \Symfony\Component\Console\Output\ConsoleSectionOutput
   */
  public static $testResult;

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents() {
    return [

      Event::RUNNER_CONFIG => [
        function (Event\RunnerEvent $event) {

          // Print base URL only when it hasn't been printed, or it's changed.
          static $base_url;
          $config_base_url = $event->getRunner()
                               ->getConfig()['base_url'] ?? NULL;
          if ($base_url !== $config_base_url) {
            $base_url = $config_base_url;
            $event->getRunner()->echo(new Message(
              [
                "    $config_base_url ",
              ],
              MessageType::INFO,
              Verbosity::NORMAL
            ), Flags::INVERT);
          }
        },
      ],
      Event::SUITE_LOADED => [
        function (Event\SuiteEventInterface $event) {
          $suite = $event->getSuite();
          self::echoSuiteTitle($suite->getRunner()
            ->getMessenger(), new Message([
            sprintf('%s%s ...', ltrim($suite->getGroup() . '/', '/'), $suite->id()),
          ], MessageType::INFO, Verbosity::VERBOSE), Flags::INVERT_FIRST_LINE);
        },
      ],

      Event::TEST_FAILED => [
        function (TestEventInterface $event) {
          $test = $event->getTest();
          $test->addMessage(new Message(
            [
              self::FAILED_PREFIX . $test,
            ],
            MessageType::ERROR,
            Verbosity::NORMAL
          ));
        },
      ],

      Event::TEST_FINISHED => [
        function (TestEventInterface $event) {
          $event->getTest()->echoMessages();
        },
      ],

      Event::SUITE_FAILED => [
        function (Event\SuiteEvent $event) {
          $suite = $event->getSuite();
          self::echoSuiteTitle($suite->getRunner()
            ->getMessenger(), new Message([
            ltrim($suite->getGroup() . '/', '/') . $suite->id(),
          ], MessageType::ERROR), Flags::INVERT_FIRST_LINE);
        },
      ],

      Event::SUITE_PASSED => [
        function (Event\SuiteEvent $event) {
          $suite = $event->getSuite();
          self::echoSuiteTitle($suite->getRunner()
            ->getMessenger(), new Message([
            ltrim($suite->getGroup() . '/', '/') . $suite->id(),
          ], MessageType::SUCCESS));
        },
      ],

      Event::TEST_STARTED => [
        function (TestEventInterface $event) {
          $test = $event->getTest();
          $config = $test->getConfig();
          if (!empty($config['why'])) {
            $test->addMessage(new Message([$config['why']], MessageType::INFO, Verbosity::VERBOSE));
          }
        },
      ],
    ];


    //    return [
    //      Event::TEST_CREATED => [
    //        function (TestEventInterface $event) {
    //          $test = $event->getTest();
    //          $config = $test->getConfig();
    //
    //          // TODO Move this to the javascript plugin.
    //          if ($config['js'] ?? FALSE) {
    //            $test->addBadge('☕');
    //          }
    //
    //          // If a "test" only contains a "why" then it will be seen as a header
    //          // and not a test, we'll do it a little differently.
    //          if (count($config) === 1 && !empty($config['why'])) {
    //            $test->setPassed();
    //            if ($test->getRunner()->getOutput()->isVerbose()) {
    //              $heading = '    ' . $test->getDescription() . ' ';
    //              self::$testTitle->overwrite([
    //                Color::wrap(self::COLOR_WHY_ONLY, $heading),
    //                '',
    //              ]);
    //              self::$testResult->clear();
    //            }
    //          }
    //          else {
    //            self::updateTestStatus($event->getTest()
    //              ->getRunner(), $test->getDescription());
    //          }
    //        },
    //        -1,
    //      ],
    //
    //      Event::TEST_FAILED => [
    //        function (TestEventInterface $event) {
    //          $test = $event->getTest();
    //          $runner = $event->getTest()->getRunner();
    //          $output = $runner->getOutput();
    //
    //          // We override the user-passed verbosity because in this case we want
    //          // to display things regardless of the actual user-provided verbosity.
    //          $stash = $output->getVerbosity();
    //          $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
    //          self::updateTestStatus($runner, sprintf('#%d: %s', $test->id(), $test->getDescription()), $test->hasPassed());
    //          $output->setVerbosity($stash);
    //
    //          //          foreach ($runner->getMessages() as $message) {
    //          //            self::$testDetails->write(strval($message));
    //          //          }
    //
    //          //
    //          // TODO Move this to another place.
    //          //
    //
    //          // Create the failure output files.
    //          if (0) {
    //            $test = $event->getTest();
    //            if (!empty($url)) {
    //              $failure_log = [$url];
    //            }
    //
    //            $runner = $test->getRunner();
    //            foreach ($runner->getMessages() as $item) {
    //              if ('error' === $item['level']) {
    //                $failure_log[] = $item['data'];
    //              }
    //            }
    //            $failure_log[] = PHP_EOL;
    //            $runner->writeToFile('failures', $failure_log);
    //
    //            $suite = $test->getSuite();
    //            FailedTestMarkdown::output("{$suite->id()}{$test->id()}", $test);
    //          }
    //
    //          //
    //          // TODO End Move this to another place.
    //          //
    //
    //        },
    //      ],
    //
    //      Event::TEST_PASSED => [
    //        function (TestEventInterface $event) {
    //          $test = $event->getTest();
    //          $runner = $event->getTest()->getRunner();
    //          $output = $runner->getOutput();
    //          if ($output->isVerbose()) {
    //            self::updateTestStatus($runner, $test->getDescription(), TRUE);
    //          }
    //          if ($output->isVeryVerbose() && $runner->getMessages()) {
    //            //            foreach ($runner->getMessages() as $message) {
    //            //              self::$testDetails->write(strval($message));
    //            //            }
    //          }
    //        },
    //      ],
    //    ];
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
  public static function echoSuiteTitle(MessengerInterface $messenger, MessageInterface $message, int $flags = NULL) {

    $messenger->addProcessor(function (array $lines, MessageInterface $message) {
      switch ($message->getMessageType()) {
        case MessageType::SUCCESS:
          $lines[0] = '👍  ' . $lines[0];
          break;

        case MessageType::ERROR:
          $lines[0] = self::FAILED_PREFIX . $lines[0];
          break;

        default:
          $lines[0] = '🔎  ' . $lines[0];
          break;
      }

      return $lines;
    })->deliver($message, $flags);

    //    return;
    //
    //
    //    $compact_mode = !$output->isVerbose() && !$output->isQuiet();
    //
    //    // In compact mode, no test details should display.
    //    if ($compact_mode) {
    //      $title = strval($message);
    //      switch ($message->getMessageType()) {
    //        case MessageType::SUCCESS:
    //          Feedback::$suiteTitle->overwrite([
    //            '👍  ' . Color::wrap('green', $title),
    //          ]);
    //          break;
    //
    //        case MessageType::ERROR:
    //          Feedback::$suiteTitle->overwrite([
    //            self::FAILED_PREFIX . Color::wrap('white on red', $title),
    //          ]);
    //          break;
    //
    //        case MessageType::TODO:
    //          Feedback::$suiteTitle->overwrite([
    //            '🔎  ' . Color::wrap(Feedback::COLOR_PENDING, $title),
    //          ]);
    //          break;
    //      }
    //    }
    //
    //    // In the following mode, the test details will follow, so we add an extra
    //    // line break and create more color.
    //    else {
    //      $title = '    ' . strtoupper($message) . ' ';
    //      switch ($message->getMessageType()) {
    //        case MessageType::SUCCESS:
    //          Feedback::$suiteTitle->overwrite([
    //            Color::wrap('white on green', $title),
    //          ]);
    //          break;
    //
    //        case MessageType::ERROR:
    //          Feedback::$suiteTitle->overwrite([
    //            Color::wrap('white on red', $title),
    //          ]);
    //          break;
    //
    //        case MessageType::TODO:
    //          Feedback::$suiteTitle->overwrite([
    //            Color::wrap('white on ' . Feedback::COLOR_PENDING_BG, $title),
    //          ]);
    //          break;
    //      }
    //    }

  }

  public static function updateTestStatus(Runner $runner, string $title, $status = NULL, $icon = NULL, string $status_text = '') {
    $input = $runner->getInput();
    $output = $runner->getOutput();

    // TODO Revisit this second half re: show.
    $should_show = $output->isVerbose() || $input->getOption('show');

    if (!$should_show) {
      return;
    }
    if (TRUE === $status) {
      self::$testTitle->overwrite(($icon ?? '👍  ') . Color::wrap('green', $title));
      //      self::$testResult->overwrite([
      //        Color::wrap('green', $status_text ?? '└── Passed.'),
      //        '',
      //      ]);
    }
    elseif (FALSE === $status) {
      self::$testTitle->overwrite(($icon ?? '🚫  ') . Color::wrap('white on red', $title));
      //      self::$testResult->overwrite([
      //        Color::wrap('red', $status_text ?? '└── Failed.'),
      //        '',
      //      ]);
    }
    else {
      self::$testTitle->overwrite(($icon ?? '🔎  ') . Color::wrap(Feedback::COLOR_PENDING, $title));
      //      self::$testResult->overwrite([
      //        Color::wrap(Feedback::COLOR_PENDING, $status_text ?? '└── Pending...'),
      //        '',
      //      ]);
    }
  }

}
