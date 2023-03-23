<?php

namespace AKlump\CheckPages\Parts;

use AKlump\CheckPages\Assert;
use AKlump\CheckPages\Browser\ChromeDriver;
use AKlump\CheckPages\Browser\GuzzleDriver;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\DriverEvent;
use AKlump\CheckPages\Event\TestEvent;
use AKlump\CheckPages\Exceptions\TestFailedException;
use AKlump\CheckPages\Output\Message;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\CheckPages\Output\YamlMessage;
use AKlump\CheckPages\Service\Assertion;
use AKlump\Messaging\MessageType;

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

    // Choose the appropriate Driver for the test.
    if ($test->getConfig()['js'] ?? FALSE) {
      $driver = new ChromeDriver();
    }
    else {
      $driver = new GuzzleDriver();
    }
    $driver->setBaseUrl($runner->get('base_url') ?? '');
    $dispatcher->dispatch(new DriverEvent($test, $driver), Event::TEST_STARTED);

    $is_http_test = $test->has('url');
    if ($is_http_test) {

      // We now must interpolate the URL, at the very last minute before the
      // request.  All event handlers have until this point to set variables and
      // modify the url for interpolation; after this, the URL is going to be
      // interpolated and set. "url" is a  skipped key when we use
      // Test::interpolate(), which is why we have to use long-hand here.
      $config = $test->getConfig();
      $test->interpolate($config['url']);
      $test->setConfig($config);
      unset($config);

      $yaml_message = $test->getConfig();
      if (empty($yaml_message['find'])) {
        unset($yaml_message['find']);
      }
      $test->addMessage(new YamlMessage($yaml_message, 0, function ($yaml) {
        // To make the output cleaner we need to remove the printed '' since find
        // is really an array, whose elements are yet to be printed.
        return str_replace("find: ''", 'find:', $yaml);
      }, MessageType::DEBUG, Verbosity::DEBUG));

      try {
        $timeout = $runner->getConfig()['request_timeout'] ?? NULL;
        if (is_int($timeout)) {
          $driver->setRequestTimeout($timeout);
        }
        // Keep this after the timeout so that plugins may override.
        $dispatcher->dispatch(new DriverEvent($test, $driver), Event::REQUEST_CREATED);

        // In some cases the first assertion is looking for a dom element that
        // may be created as a result of an asynchronous JS event.  We create an
        // assertion to pass to the driver, which can be used by the driver as a
        // "wait for this assertion to pass" signal.
        $variables = $test->getSuite()->variables();
        $assertions_to_wait_for = array_filter($test->getConfig()['find'] ?? [], function ($config) use ($variables) {

          // Quick check against certain keys...
          if (!is_array($config)
            || !array_intersect_key(array_flip(['dom', 'xpath']), $config)
            || array_intersect_key(array_flip(['style']), $config)
          ) {
            return FALSE;
          }

          // ... now make sure it is fully interpolated.
          $variables->interpolate($config);

          return $variables->needsInterpolation($config) === FALSE;
        });
        $assertions_to_wait_for = array_map(function (array $config) {
          return Assertion::create($config);
        }, $assertions_to_wait_for);

        $driver->setUrl($runner->withBaseUrl($test->getConfig()['url']));
        $dispatcher->dispatch(new DriverEvent($test, $driver), Event::REQUEST_PREPARED);
        $driver->request($assertions_to_wait_for);
      }
      catch (\Exception $exception) {
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

      $dispatcher->dispatch(new DriverEvent($test, $driver), Event::REQUEST_FINISHED);
    }

    try {
      $assertions = $test->get('find') ?? [];
      if (count($assertions) === 0) {
        //        $test->addMessage(new Message([
        //          'This test has no assertions.',
        //        ], MessageType::DEBUG, Verbosity::DEBUG));
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
          if ($assert->hasFailed()) {
            $test->setFailed();
          }
          ++$id;
        }
      }

      // All assertions are done, if we haven't failed by now then the test can
      // be marked as having passed.
      if (!$test->hasFailed()) {
        $test->setPassed();
      }
    }
    catch (\Exception $exception) {
      throw new TestFailedException($test->getConfig(), $exception);
    }
    finally {
      $dispatcher->dispatch(new DriverEvent($test, $driver), Event::TEST_FINISHED);
    }
  }

}
