<?php

namespace AKlump\CheckPages\Handlers;

use AKlump\CheckPages\Browser\RequestDriverInterface;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\DriverEventInterface;
use AKlump\CheckPages\Exceptions\TestFailedException;
use AKlump\CheckPages\Output\Message;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\CheckPages\Traits\SetTrait;
use AKlump\CheckPages\Parts\Test;
use AKlump\Messaging\MessageType;
use Exception;

/**
 * Implements the Redirect handler.
 */
final class Redirect implements HandlerInterface {

  use SetTrait;

  /**
   * {@inheritdoc}
   */
  public static function getId(): string {
    return 'redirect';
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    // If a class instance is not needed you can simplify this by doing
    // everything inside of the function, rather than instantiating and calling
    // a method.  Depends on implementation.
    return [

      // TODO Rewrite this to allow for matches, contains, etc, maybe move to find?
      Event::REQUEST_FINISHED => [
        function (DriverEventInterface $event) {
          $handler = new Redirect();
          $test = $event->getTest();
          $driver = $event->getDriver();
          try {
            $expected_status_code = $handler->assertStatusCode($test, $driver);
            if ($test->has('redirect') || $test->has('location')) {
              $handler->assertLocation($test, $driver);
            }

            // With redirects, capture the redirect context into variables.
            if ($test->has('status') && $expected_status_code[0] === '3') {
              $variables = $test->getSuite()->variables();
              $lines = [];
              $lines[] = $handler->setKeyValuePair($variables, 'redirect.location', $driver->getLocation());
              $lines[] = $handler->setKeyValuePair($variables, 'redirect.status', $driver->getRedirectCode());
              $test->addMessage(new Message($lines, MessageType::DEBUG, Verbosity::DEBUG));
            }
          }
          catch (Exception $e) {
            throw new TestFailedException($test->getConfig(), $e);
          }
        },
      ],
    ];
  }

  protected function assertLocation(Test $test, RequestDriverInterface $driver) {
    $expected_location = $this->getExpectedLocationByTest($test);
    if (NULL === $expected_location) {
      return;
    }

    $actual_location = $driver->getLocation();
    if ($actual_location === $expected_location) {
      $test->setPassed();
    }
    else {
      $test->setFailed();
      $test->addMessage(new Message([
        sprintf('The actual "%s" and expected "%s" locations do not match', $actual_location, $expected_location),
      ], MessageType::ERROR, Verbosity::VERBOSE));
    }
  }

  /**
   * @param \AKlump\CheckPages\Parts\Test $test
   *
   * @return string|null
   */
  private function getExpectedLocationByTest(Test $test) {
    $expected_location = NULL;
    if ($test->has('location')) {
      $expected_location = $test->get('location') ?? '';
    }
    if (empty($expected_location) && $test->has('redirect')) {
      $expected_location = $test->get('redirect') ?? '';
    }
    if (isset($expected_location)) {
      $test->interpolate($expected_location);
    }

    return $expected_location;
  }

  /**
   * @param \AKlump\CheckPages\Parts\Test $test
   * @param \AKlump\CheckPages\Browser\RequestDriverInterface $driver
   *
   * @return string This can be 200 or 2xx, hence a string.
   */
  protected function assertStatusCode(Test $test, RequestDriverInterface $driver): string {
    $actual_status_code = $driver->getResponse()->getStatusCode();
    $actual_status_first_char = substr($actual_status_code, 0, 1);
    $default_status_code = '2xx';
    $send_message = FALSE;
    if (!$test->has('status')) {
      $expected_status_code = $default_status_code;
      if ($actual_status_first_char !== '2') {
        $test->setFailed();
        $send_message = TRUE;
      }
    }
    else {
      $expected_status_code = $test->get('status') ?: $default_status_code;
      $expected_status_first_char = substr($expected_status_code, 0, 1);
      if ($expected_status_first_char === '3') {
        $actual_status_code = $driver->getRedirectCode();
      }
      if ($actual_status_code != $expected_status_code) {
        $test->setFailed();
        $send_message = TRUE;
      }
    }
    if ($send_message) {
      $server = $driver->getResponse()->getHeader('server')[0] ?? NULL;
      if ($server) {
        $server = " $server";
      }

      $test->addMessage(new Message([
        sprintf('The server%s returned %s, which is not the expected %s', $server, $actual_status_code, $expected_status_code),
      ], MessageType::ERROR, Verbosity::VERBOSE));
    }

    return $expected_status_code ?: '2xx';
  }

}
