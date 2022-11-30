<?php

namespace AKlump\CheckPages\Parts;

use AKlump\CheckPages\Assert;
use AKlump\CheckPages\ChromeDriver;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\DriverEvent;
use AKlump\CheckPages\Event\TestEvent;
use AKlump\CheckPages\Exceptions\TestFailedException;
use AKlump\CheckPages\GuzzleDriver;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\CheckPages\Service\Assertion;
use AKlump\Messaging\MessageType;
use AKlump\CheckPages\Output\Message;
use AKlump\CheckPages\Output\YamlMessage;

class TestRunner {

  /**
   * Run a given test.
   *
   * @param \AKlump\CheckPages\Parts\Test $test
   *
   * @throws \AKlump\CheckPages\Exceptions\TestFailedException
   */
  public function run(Test $test) {
    $runner = $test->getRunner();
    $dispatcher = $runner->getDispatcher();

    $dispatcher->dispatch(new TestEvent($test), Event::TEST_STARTED);

    $test_passed = function (bool $result = NULL): bool {
      static $state;
      if (!is_null($result)) {
        $state = is_null($state) || $state ? $result : FALSE;
      }

      return boolval($state);
    };

    $driver = new GuzzleDriver();
    $is_http_test = !empty($test->getConfig()['url']);
    if ($is_http_test) {

      $yaml_message = $test->getConfig();
      $yaml_message['find'] = '';
      $test->addMessage(new YamlMessage($yaml_message, 0, function ($yaml) {
        // To make the output cleaner we need to remove the printed '' since find
        // is really an array, whose elements are yet to be printed.
        return str_replace("find: ''", 'find:', $yaml);
      }, MessageType::DEBUG, Verbosity::DEBUG));

      if ($test->getConfig()['js'] ?? FALSE) {
        try {
          if (empty($runner->getConfig()['chrome'])) {
            throw new \InvalidArgumentException(sprintf("Javascript testing is unavailable due to missing path to Chrome binary.  Add \"chrome\" in file %s.", $runner->getLoadedConfigPath()));
          }
          $driver = new ChromeDriver($runner->getConfig()['chrome']);
        }
        catch (\Exception $exception) {
          throw new TestFailedException($test->getConfig(), $exception);
        }
      }

      // We now must interpolate the URL, at the very last minute.  All event
      // handlers have until this point to set variables and modify the url for
      // interpolation; after this, the URL is going to be interpolated and set.
      // "url" is a  skipped key when we use Test::interpolate(), which is why
      // we have to use long-hand here.
      $config = $test->getConfig();
      $test->interpolate($config['url']);
      $test->setConfig($config);
      unset($config);

      $dispatcher->dispatch(new DriverEvent($test, $driver), Event::REQUEST_CREATED);

      try {
        // The config-level override...
        $timeout = $runner->getConfig()['request_timeout'] ?? NULL;
        // ... the test-level override.
        $timeout = $test->getConfig()["request"]["timeout"] ?? $timeout;
        if (is_int($timeout)) {
          $driver->setRequestTimeout($timeout);
        }

        // In some cases the first assertion is looking for a dom element that
        // may be created as a result of an asynchronous JS event.  We create an
        // assertion to pass to the driver, which can be used by the driver as a
        // "wait for this assertion to pass" signal.
        $wait_for = array_filter($test->getConfig()['find'] ?? [], function ($config) {
          // Choose asserts based on IF they HAVE keys and NOT other keys.
          return is_array($config)
            && array_intersect_key(array_flip(['dom']), $config)
            && !array_intersect_key(array_flip(['style']), $config);
        });
        $wait_for = array_map(function (array $config) {
          return Assertion::create($config);
        }, $wait_for);

        $response = $driver
          ->setUrl($runner->url($test->getConfig()['url']))
          ->request($wait_for)
          ->getResponse();
        $http_response_code = $response->getStatusCode();
      }
      catch (\Exception $exception) {
        $response = NULL;

        if (method_exists($exception, 'getResponse')) {
          $response = $exception->getResponse();
          if ($response) {
            $http_response_code = $response->getStatusCode();
          }
        }

        if (empty($http_response_code)) {
          $runner->handleFailedRequestNoResponse($test, $driver, $exception);
        }
      }

      $http_location = NULL;

      // If not specified, then any 2XX will pass.
      if (empty($test->getConfig()['expect'])) {
        $test_passed($http_response_code >= 200 && $http_response_code <= 299);
      }
      else {
        if ($test->getConfig()['expect'] >= 300 && $test->getConfig()['expect'] <= 399) {
          $http_location = $driver->getLocation();
          $http_response_code = $driver->getRedirectCode();
        }

        $test_passed($http_response_code == $test->getConfig()['expect']);
      }

      if (!$test_passed()) {
        $test->setFailed();
      }
      $dispatcher->dispatch(new DriverEvent($test, $driver), Event::REQUEST_FINISHED);

      // Test the location if asked.
      $expected_location = $test->getConfig()['location'] ?? '';
      if (empty($expected_location)) {
        $expected_location = $test->getConfig()['redirect'] ?? '';
      }
      $test->interpolate($expected_location);

      if ($http_location && $expected_location) {
        $location_test = $http_location === $runner->url($expected_location);
        $test_passed($location_test);
        if (!$location_test) {
          $test->addMessage(new Message(
            [
              sprintf('The actual location: %s did not match the expected location: %s', $http_location, $runner->url($expected_location)),
            ],
            MessageType::ERROR,
            Verbosity::VERBOSE
          ));
        }
      }
    }

    $assertions = $test->getConfig()['find'] ?? [];
    if (count($assertions) === 0) {
      $test_passed(TRUE);
      $test->addMessage(new Message([
        'This test has no assertions.',
      ], MessageType::DEBUG, Verbosity::DEBUG));
    }
    else {
      $id = 0;
      $assert_runner = new AssertRunner($driver);
      while ($definition = array_shift($assertions)) {
        if (is_scalar($definition)) {
          $definition = [Assert::ASSERT_CONTAINS => $definition];
        }

        $test->interpolate($definition);
        $test->addMessage(new YamlMessage($definition, 2, NULL, MessageType::DEBUG, Verbosity::DEBUG));

        $assert = $assert_runner->run(new Assert($id, $definition, $test));
        $test_passed($assert->hasPassed());

        ++$id;
      }
    }

    if ($test_passed()) {
      $test->setPassed();
    }
    else {
      $test->setFailed();
    }

    $dispatcher->dispatch(new DriverEvent($test, $driver), Event::REQUEST_TEST_FINISHED);
  }

}
