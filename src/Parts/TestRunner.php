<?php

namespace AKlump\CheckPages\Parts;

use AKlump\CheckPages\Assert;
use AKlump\CheckPages\Browser\ChromeDriver;
use AKlump\CheckPages\Browser\GuzzleDriver;
use AKlump\CheckPages\Browser\RequestDriverInterface;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\DriverEvent;
use AKlump\CheckPages\Event\TestEvent;
use AKlump\CheckPages\Exceptions\RequestTimedOut;
use AKlump\CheckPages\Output\DebugMessage;
use AKlump\CheckPages\Output\Message;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\CheckPages\Output\YamlMessage;
use AKlump\CheckPages\Service\Assertion;
use AKlump\Messaging\MessageType;
use Psr\EventDispatcher\EventDispatcherInterface;

final class TestRunner implements EventDispatcherInterface {

  /**
   * @var \AKlump\CheckPages\Browser\ChromeDriver|\AKlump\CheckPages\Browser\GuzzleDriver
   */
  private $driver;

  /**
   * @var \AKlump\CheckPages\Parts\Test
   */
  private $test;

  public function __construct(Test $test) {
    $this->test = $test;
  }

  /**
   * Start the test before calling run.
   *
   * This method starts the test by dispatching the TEST_CREATED event.  It
   * should be called before calling run() and if the test passes or fails in
   * this phase, run should not be called.
   *
   * @return \AKlump\CheckPages\Parts\TestRunner
   *   The current TestRunner instance.
   */
  public function start(): TestRunner {
    $this->test->getRunner()
      ->getDispatcher()
      ->dispatch(new TestEvent($this->test), Event::TEST_CREATED);

    return $this;
  }

  public function getDriver(): RequestDriverInterface {
    if (empty($this->driver)) {
      $test = $this->test;
      if ($test->get('js') ?? FALSE) {
        $this->driver = new ChromeDriver();
      }
      else {
        $this->driver = new GuzzleDriver();
      }
      $this->driver->setMessenger($test->getRunner()->getMessenger());
    };

    return $this->driver;
  }

  public function dispatch(object $event, string $eventName = NULL): object {
    $this->test->getRunner()->getDispatcher()->dispatch($event, $eventName);

    return $event;
  }

  /**
   * Run a test that has neither passed nor failed.
   *
   * @return \AKlump\CheckPages\Parts\TestRunner
   *   Self for chaining.
   *
   * @throws \RuntimeException If the test has already passed or failed.
   */
  public function run(): TestRunner {
    $this->tryValidateTestCanBeRun($this->test);

    $test = $this->test;
    $runner = $test->getRunner();
    $this->getDriver()->setBaseUrl($runner->get('base_url') ?? '');
    $this->dispatch(new DriverEvent($test, $this->getDriver()), Event::TEST_STARTED);

    //
    // Do the HTTP part of the test.
    //
    $is_http_test = $test->has('url');
    if ($is_http_test) {

      // We now must interpolate the URL, at the very last minute before the
      // request.  All event handlers have until this point to set variables and
      // modify the url for interpolation; after this, the URL is going to be
      // interpolated and set. "url" is a  skipped key when we use
      // Test::interpolate(), which is why we have to use long-hand here.
      $config = $test->getConfig();

      // Do not interpolate 'find' yet!!!
      $test->interpolate($config['url']);
      $test->setConfig($config);
      unset($config);

      $yaml_message = $test->getConfig();

      // BEWARE REFACTORING THIS.  READ THE COMMENTS BELOW ABOUT FIND
      // INTERPOLATION TIMING.
      if (empty($yaml_message['find'])) {
        unset($yaml_message['find']);
      }
      else {
        $yaml_message['find'] = '';
      }
      $test->addMessage(new YamlMessage($yaml_message, 0, function ($yaml) {
        // To make the output cleaner we need to remove the printed '' since
        // find is really an array, whose elements are yet to be printed, and
        // which will be printed further down.
        return str_replace("find: ''", 'find:', $yaml);
      }, MessageType::DEBUG, Verbosity::DEBUG));

      try {
        $timeout = $runner->getConfig()['request_timeout'] ?? NULL;
        if (is_int($timeout)) {
          $this->getDriver()->setRequestTimeout($timeout);
        }
        // Keep this after the timeout so that handlers may override.
        $this->dispatch(new DriverEvent($test, $this->getDriver()), Event::REQUEST_CREATED);
        $this->getDriver()
          ->setUrl($runner->withBaseUrl($test->getConfig()['url'] ?? ''));
        // TODO Do we really need this event?
        $this->dispatch(new DriverEvent($test, $this->getDriver()), Event::REQUEST_PREPARED);

        // In some cases the first assertion is looking for a dom element that
        // may be created as a result of an asynchronous JS event.  We create an
        // assertion to pass to the driver, which can be used by the driver as a
        // "wait for this assertion to pass" signal.
        $variables = $test->getSuite()->variables();

        $assertions_to_wait_for = [];
        foreach (($test->get('find') ?? []) as $config) {
          if (!is_array($config)
            || !array_intersect_key(array_flip(['dom', 'xpath']), $config)
            || array_intersect_key(array_flip(['style']), $config)
          ) {
            continue;
          }

          $variables->interpolate($config);
          // If the selector is not fully interpolated then we cannot wait for
          // it, so that's why we do this check here.
          if (!$variables->needsInterpolation($config)) {
            $assertions_to_wait_for[] = Assertion::create($config);
          }
        }
        $this->getDriver()->request($assertions_to_wait_for);
      }
      catch (RequestTimedOut $exception) {
        $test->setFailed();
        $test->addMessage(new Message([$exception->getMessage()], MessageType::ERROR, Verbosity::VERBOSE));
        $test->addMessage(new DebugMessage(
            [
              sprintf('Try setting a value higher than %d for "request_timeout" in %s, or at the test level.', $this->getDriver()
                ->getRequestTimeout(), basename($runner->getLoadedConfigPath())),
            ],
            MessageType::TODO)
        );
      }
      $this->dispatch(new DriverEvent($test, $this->getDriver()), Event::REQUEST_FINISHED);
    }

    //
    // Do the assertions.
    //
    $assertions = $test->get('find') ?? [];
    if (!$test->hasFailed() && count($assertions) > 0) {
      $id = 0;
      $assert_runner = new AssertRunner($this->getDriver());
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

    return $this;
  }

  /**
   * Process a test after running it.
   *
   * @return bool
   *   The runtime test result.  This may be an inversion of the result as
   *   stored in the test object (which is the truth) because of the inversion
   *   concept of "expected outcome".
   */
  public function processResult(): bool {
    $test = $this->test;

    // Assume the best in people.
    if (!$test->hasFailed() && !$test->isSkipped()) {
      $test->setPassed();
    }

    $runtime_test_result = !$test->hasFailed();

    // Allow the result to be inverted by the test config.  This will cause the
    // opposite events to be called, but does not actually change the test value
    // on the object instance.
    if ($test->has('expected outcome')) {
      $expected_outcome = $test->get('expected outcome');
      if (!$runtime_test_result && in_array($expected_outcome, [
          'fail',
          'failure',
        ])) {
        $runtime_test_result = TRUE;

        // Make all error messages success messages so as not to catch the eye.
        $messages = array_map(function (Message $message) {
          $type = $message->getMessageType();
          if ($type === MessageType::ERROR) {
            $message->setMessageType(MessageType::SUCCESS);
          }

          return $message;
        }, $test->getMessages());
        $test->setMessages($messages);

        $test->addMessage(new Message([
          'This test failed as expected.',
        ], MessageType::SUCCESS, Verbosity::VERBOSE));
      }
    }

    if ($runtime_test_result) {
      $this->dispatch(new TestEvent($test), Event::TEST_PASSED);
    }
    elseif ('skip suite' === $test->get('on fail')) {
      $test->setIsSkipped(TRUE);
      $test->getSuite()->setIsSkipped(TRUE);
      $this->dispatch(new TestEvent($test), Event::TEST_SKIPPED);
    }
    else {
      $test->getSuite()->setFailed();
      $this->dispatch(new TestEvent($test), Event::TEST_FAILED);
    }

    $this->dispatch(new DriverEvent($test, $this->getDriver()), Event::TEST_FINISHED);

    return $runtime_test_result || $test->isSkipped();
  }

  private function tryValidateTestCanBeRun(Test $test) {
    if ($test->hasFailed() || $test->hasPassed()) {
      throw new \RuntimeException('It is not possible to run a test that has already passed/failed.');
    }
  }
}
