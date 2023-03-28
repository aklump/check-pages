<?php

namespace AKlump\CheckPages\Output;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\DriverEventInterface;
use AKlump\CheckPages\Event\SuiteEventInterface;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\SerializationTrait;
use AKlump\LoftLib\Bash\Color;
use AKlump\Messaging\Processors\Messenger;
use AKlump\Messaging\MessageInterface;
use AKlump\Messaging\MessageType;
use AKlump\Messaging\MessengerInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Provides user feedback based on output verbosity.
 */
final class Feedback implements EventSubscriberInterface {

  use SerializationTrait;

  /**
   * This must be a background color.
   *
   * @var string
   */
  const FAILED_PREFIX = '🚫 FAILED: ';

  const COLOR_PENDING = 'purple';

  const COLOR_PENDING_BG = 'magenta';

  const SOURCE_CODE_MESSAGE_TYPE = MessageType::INFO;

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

      Event::RUNNER_CREATED => [
        function (Event\RunnerEvent $event) {
          $runner = $event->getRunner();
          $runner->echo(new Message(
            ["    {$runner->get('base_url')} "],
            MessageType::INFO,
            Verbosity::NORMAL
          ), Flags::INVERT);
        },
      ],

      Event::SUITE_STARTED => [
        function (SuiteEventInterface $event) {
          $suite = $event->getSuite();
          self::echoSuiteTitle($suite->getRunner()
            ->getMessenger(), new Message([
            sprintf('%s ...', $suite),
          ], MessageType::INFO, Verbosity::VERBOSE), Flags::INVERT_FIRST_LINE);
        },
      ],

      Event::TEST_STARTED => [
        function (DriverEventInterface $event) {
          $test = $event->getTest();
          if ($test->has('why')) {
            $test->addMessage(new Message([$test->get('why')], MessageType::INFO, Verbosity::VERBOSE));
          }
        },
      ],

      Event::REQUEST_PREPARED => [
        function (DriverEventInterface $event) {
          $test = $event->getTest();

          //
          // The URL.
          //
          $url = $test->getHttpMethod() . ' ' . $test->getAbsoluteUrl();
          if (trim($url)) {
            $test->addMessage(new Message([
              $url,
            ], self::SOURCE_CODE_MESSAGE_TYPE, Verbosity::VERBOSE | Verbosity::REQUEST | Verbosity::HEADERS | Verbosity::RESPONSE));
          }

          $input = $test->getRunner()->getInput();
          //          $show = new VerboseDirective(strval($input->getOption('show')));
          $handler = new self();
          $driver = $event->getDriver();

          //
          // Request Headers
          //
          //          if ($show->showSendHeaders()) {
          $headers = $driver->getHeaders();
          $headers = $handler->prepareHeadersMessage($headers);
          if ($headers) {
            $test->addMessage(new Message(
              array_merge($headers, ['']),
              self::SOURCE_CODE_MESSAGE_TYPE,
              Verbosity::HEADERS
            ));
          }
          //          }

          //
          // Request Body
          //
          $request_body = $handler->prepareContentMessage($input, strval($driver), $handler->getContentType($driver));
          if ($request_body) {
            $test->addMessage(new Message([
              $request_body,
              '',
            ], self::SOURCE_CODE_MESSAGE_TYPE, Verbosity::REQUEST));
          }
        },
      ],

      Event::REQUEST_FINISHED => [
        function (DriverEventInterface $event) {
          $test = $event->getTest();

          $message_type = self::SOURCE_CODE_MESSAGE_TYPE;
          if ($test->hasFailed()) {
            $message_type = MessageType::ERROR;
          }

          $driver = $event->getDriver();
          $input = $test->getRunner()->getInput();

          //
          // Request
          //
          //          $url = $test->getHttpMethod() . ' ' . $test->getAbsoluteUrl();
          //          if (trim($url)) {
          //            $test->addMessage(new Message([
          //              $url,
          //            ], $message_type));
          //          }

          //          $handler = new self();
          //          $headers = $driver->getHeaders();
          //          $headers = $handler->prepareHeadersMessage($headers);
          //          if ($headers) {
          //            $test->addMessage(new DebugMessage(['Response Headers']));
          //            $test->addMessage(new Message(
          //              array_merge($headers, ['']),
          //              $message_type,
          //              Verbosity::HEADERS
          //            ));
          //          }
          //
          //          $body = $handler->prepareContentMessage($input, strval($driver), $handler->getContentType($driver));
          //          if ($body) {
          //            $test->addMessage(new DebugMessage(['Response Body']));
          //            $test->addMessage(new Message([
          //              $body,
          //              '',
          //            ], MessageType::DEBUG,
          //              Verbosity::REQUEST
          //            ));
          //          }

          //
          // Response
          //
          $handler = new self();
          $response = $event->getDriver()->getResponse();

          $headers = $response->getHeaders();
          $headers = $handler->prepareHeadersMessage($headers);
          if ($headers) {
            $test->addMessage(new Message(
              array_merge($headers, ['']),
              $message_type,
              Verbosity::HEADERS
            ));
          }

          $lines = [];
          $lines[] = $handler->getResponseHttpStatusLine($test, $response);
          $lines[] = $handler->prepareContentMessage($input, $response->getBody(), $handler->getContentType($driver));
          if (array_filter($lines)) {
            $test->addMessage(new Message(array_merge($lines, ['']), $message_type, Verbosity::RESPONSE));
          }
        },
      ],

      Event::TEST_FAILED => [
        function (TestEventInterface $event) {
          $test = $event->getTest();
          $test->addMessage(new Message([
            self::FAILED_PREFIX . $test,
          ], MessageType::ERROR, Verbosity::NORMAL));
        },
      ],

      Event::TEST_FINISHED => [
        function (TestEventInterface $event) {
          $test = $event->getTest();

          // This will add some space before the next test is run.
          $test->addMessage(new Message([''], MessageType::INFO, Verbosity::VERBOSE));
          $test->echoMessages();
        },
      ],

      Event::SUITE_FAILED => [
        function (Event\SuiteEvent $event) {
          $suite = $event->getSuite();
          $lines = [strval($suite)];
          if ($suite->getRunner()->getOutput()->isVerbose()) {
            $lines[] = '';
          }
          self::echoSuiteTitle($suite->getRunner()
            ->getMessenger(), new Message($lines, MessageType::ERROR), Flags::INVERT_FIRST_LINE);
        },
      ],

      Event::SUITE_PASSED => [
        function (Event\SuiteEvent $event) {
          $suite = $event->getSuite();
          $lines = [strval($suite)];
          if ($suite->getRunner()->getOutput()->isVerbose()) {
            $lines[] = '';
          }
          self::echoSuiteTitle($suite->getRunner()
            ->getMessenger(), new Message($lines, MessageType::SUCCESS));
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

  /**
   * @param \AKlump\CheckPages\Parts\Test $test
   * @param \Psr\Http\Message\ResponseInterface $response
   *
   * @return string
   *   A string that shows the response info.
   */
  private function getResponseHttpStatusLine(Test $test, ResponseInterface $response) {
    return sprintf('%s/%s %d %s',
      strtoupper(parse_url($test->getAbsoluteUrl(), PHP_URL_SCHEME)),
      $response->getProtocolVersion(),
      $response->getStatusCode(),
      $response->getReasonPhrase()
    );
  }

  /**
   * Convert headers to message lines.
   *
   * @param array $raw_headers
   *
   * @return array
   *   An array ready for \AKlump\Messaging\MessageInterface(
   */
  private function prepareHeadersMessage(array $raw_headers): array {
    $raw_headers = array_filter($raw_headers);
    if (empty($raw_headers)) {
      return [];
    }

    $lines = [];
    foreach ($raw_headers as $name => $value) {
      if (!is_array($value)) {
        $value = [$value];
      }
      foreach ($value as $item) {
        $lines[] = sprintf('%s: %s', $name, $item);
      }
    }

    return $lines;
  }

  /**
   * Format content per content type for message lines.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param string $content
   * @param string $content_type
   *
   * @return string
   *   An array ready for \AKlump\Messaging\MessageInterface
   */
  private function prepareContentMessage(InputInterface $input, string $content, string $content_type): string {
    $content = $this->truncate($input, $content);
    if ($content) {
      try {
        // Make JSON content type pretty-printed for readability.
        if (strstr($content_type, 'json')) {
          $data = $this->deserialize($content, $content_type);
          $content = json_encode($data, JSON_PRETTY_PRINT);
        }
      }
      catch (\Exception $exception) {
        // Purposely left blank.
      }
    }

    return $content;
  }

  /**
   * Truncate $string when appropriate.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param string $string
   *
   * @return string
   *   The truncated string.
   */
  private function truncate(InputInterface $input, string $string): string {
    $string = trim($string);
    if ($string) {
      $length = $input->getOption('truncate');
      if ($length > 0 && strlen($string) > $length) {
        return substr($string, 0, $length) . '...';
      }
    }

    return $string;
  }
}
