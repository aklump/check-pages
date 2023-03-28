<?php

namespace AKlump\CheckPages\Handlers;

use AKlump\CheckPages\Browser\HeadlessBrowserInterface;
use AKlump\CheckPages\Browser\RequestDriverInterface;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\SuiteEventInterface;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Exceptions\TestFailedException;
use AKlump\CheckPages\Output\DebugMessage;
use AKlump\CheckPages\SerializationTrait;
use AKlump\Messaging\MessageType;
use Exception;

/**
 * Implements the Request handler.
 */
final class Request implements HandlerInterface {

  use SerializationTrait;

  /**
   * Captures the test config to share across methods.
   *
   * @var array
   */
  private $request;

  /**
   * @var array
   */
  private $config;

  const SELECTOR = 'request';

  /**
   * {@inheritdoc}
   */
  public static function getId(): string {
    return 'request';
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    // If a class instance is not needed you can simplify this by doing
    // everything inside of the function, rather than instantiating and calling
    // a method.  Depends on implementation.
    return [
      Event::SUITE_STARTED => [
        function (SuiteEventInterface $event) {
          $suite = $event->getSuite();
          $tests = $suite->getTests();
          if (empty($tests)) {
            return;
          }

          $items = [];
          foreach ($suite->getTests() as $test) {
            $config = $test->getConfig();
            if (empty($config['request']['methods'])) {
              continue;
            }
            $replacements = [];
            foreach ($config['request']['methods'] as $method) {
              // Create a variable that can be interpolated.
              $replacements[] = [
                'value' => $method,
                'set' => 'request.method',
              ];

              // Create the HTTP method request.
              $replacement = $config;
              $replacement['request']['method'] = $method;
              unset($replacement['request']['methods']);
              $replacements[] = $replacement;
            }
            $items[] = [$test, $replacements];
          }

          foreach ($items as $item) {
            list($test, $replacements) = $item;
            $suite->replaceTestWithMultiple($test, $replacements);
          }
        },
      ],

      Event::TEST_CREATED => [
        function (TestEventInterface $event) {
          $test = $event->getTest();
          if (!$test->has(self::SELECTOR)) {
            return;
          }

          try {
            // Interpolate before we do anything.
            $config = $test->getConfig();
            $test->interpolate($config[self::SELECTOR]);
            $test->setConfig($config);
          }
          catch (Exception $e) {
            throw new TestFailedException($test->getConfig(), $e);
          }
        },
      ],
      Event::REQUEST_CREATED => [
        function (Event\DriverEventInterface $event) {
          $test = $event->getTest();
          if (!$test->has(self::SELECTOR)) {
            return;
          }

          try {
            $driver = $event->getDriver();
            $config = $test->getConfig();
            $interpolation_review = [];

            $handler = new self();

            //
            // Method
            //
            $http_method = strtoupper($config[self::SELECTOR]['method'] ?? 'get');

            // TODO Make sure this works with JS or not.
            //            $handler->validateDriver($driver, $http_method, $config);
            $driver->setMethod($http_method);
            $interpolation_review['method'] = $http_method;

            //
            // Headers
            //
            $headers = $config[self::SELECTOR]['headers'] ?? [];
            if ($headers) {
              $headers = array_change_key_case($headers);
              $headers = array_filter($headers, function ($value) {
                return !empty($value);
              });
              $headers = array_map('strval', $headers);
            }
            $interpolation_review['headers'] = $headers;//
            // Body
            //
            $body = $config[self::SELECTOR]['body'] ?? '';
            $body = $handler->getEncodedBody($headers, $body);
            $driver->setBody($body);
            $interpolation_review['body'] = $body;// Interpolation check debugging.  This step will look for
            // un-interpolated values in the request and send out a debug message,
            // which may help developers troubleshoot failing tests.
            if ($test->getSuite()->variables()
              ->needsInterpolation($interpolation_review)) {
              $event->getTest()
                ->addMessage(new DebugMessage([
                  'Check variables; the request appears to still need interpolation.',
                ], MessageType::DEBUG));
            }//
            // Set the timeout on the request.
            //
            $timeout = $config[self::SELECTOR]['timeout'] ?? null;
            if (is_numeric($timeout)) {
              $driver->setRequestTimeout($timeout);
            }
            foreach ($headers as $key => $value) {
              $driver->setHeader($key, $value);
            }
          }
          catch (Exception $e) {
            throw new TestFailedException($config, $e);
          }
        },
      ],
    ];
  }

  private function validateDriver(RequestDriverInterface $driver, string $http_method, array $config) {
    if (!$driver instanceof HeadlessBrowserInterface) {
      if ('GET' === $http_method && !array_diff(array_keys($config[self::SELECTOR]), [
          'method',
          'timeout',
        ])) {
        // We assume all drivers can handle a timeout change for GET.
      }
      elseif (!empty($config['js'])) {
        // Only the GuzzleDriver has been tested to work with this handler.  When
        // JS is false the GuzzleDriver is not used.
        throw new TestFailedException($config, '"js" must be set to false when using "request".');
      }
      else {
        throw new TestFailedException($config, sprintf('The %s driver is not supported by the request handler.', get_class($driver)));
      }
    }
  }

  private function getEncodedBody($headers, $body) {
    if (!$body || is_scalar($body)) {
      return $body;
    }

    $content_type = 'application/octet-stream';
    foreach ($headers as $header => $value) {
      if (strcasecmp('content-type', $header) === 0) {
        $content_type = $value;
        break;
      }
    }

    return $this->serialize($body, $content_type);
  }

}
