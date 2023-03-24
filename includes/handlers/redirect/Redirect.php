<?php

namespace AKlump\CheckPages\Handlers;

use AKlump\CheckPages\Browser\RequestDriverInterface;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\DriverEventInterface;
use AKlump\CheckPages\Exceptions\TestFailedException;
use AKlump\CheckPages\Output\Message;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\CheckPages\Parts\SetTrait;
use AKlump\CheckPages\Parts\Test;
use AKlump\Messaging\MessageType;

/**
 * Implements the Redirect handler.
 */
final class Redirect implements \AKlump\CheckPages\Handlers\HandlerInterface {

  //  const SELECTOR = 'redirect';

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
            $handler->checkStatusCode($test, $driver);
            if ($test->has('redirect') || $test->has('location')) {
              $handler->checkRedirect($test, $driver);
            }
          }
          catch (\Exception $e) {
            throw new TestFailedException($test->getConfig(), $e);
          }
        },
      ],
    ];
  }

  protected function checkRedirect(Test $test, RequestDriverInterface $driver) {
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

    $http_location = $driver->getLocation();
    if (!is_null($expected_location)) {
      $location_test = $http_location === $expected_location;
      if ($location_test) {
        $test->setPassed();
      }
      else {
        $test->setFailed();
        $test->addMessage(new Message([
          sprintf('The actual "%s" and expected "%s" locations do not match', $http_location, $expected_location),
        ], MessageType::ERROR, Verbosity::VERBOSE));
      }
    }
  }

  protected function checkStatusCode(Test $test, RequestDriverInterface $driver) {
    $actual_status_code = $driver->getResponse()->getStatusCode();
    $actual_status_first_char = substr($actual_status_code, 0, 1);

    $send_message = FALSE;
    if (!$test->has('expect')) {
      $expected_status_code = '2xx';
      if ($actual_status_first_char !== '2') {
        $test->setFailed();
        $send_message = TRUE;
      }
    }
    else {
      $expected_status_code = $test->get('expect');
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
      $test->addMessage(new Message([
        sprintf('The actual response code %s did not match the expected %s', $actual_status_code, $expected_status_code),
      ], MessageType::ERROR, Verbosity::VERBOSE));
    }
  }

}
