<?php

namespace AKlump\CheckPages;

use AKlump\CheckPages\Browser\GuzzleDriver;
use AKlump\CheckPages\Browser\RequestDriverInterface;
use AKlump\CheckPages\Event\DriverEvent;
use AKlump\CheckPages\Output\HttpMessageLogger;
use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\Traits\HasRunnerTrait;
use AKlump\Messaging\HasMessagesInterface;
use AKlump\Messaging\MessageType;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HttpClient implements ClientInterface {

  use HasRunnerTrait;

  /**
   * @var \AKlump\CheckPages\Parts\Runner
   */
  private $runner;

  /**
   * @var \AKlump\Messaging\HasMessagesInterface
   */
  private $messageBag;

  /**
   * @var \AKlump\CheckPages\Parts\Test
   */
  private $testForDispatcher;

  /**
   * @var array
   */
  private $stashed = [];

  static $class_instance_id = 0;

  /**
   * @param \AKlump\CheckPages\Parts\Runner $runner
   * @param \AKlump\Messaging\HasMessagesInterface $message_bag
   *   Request messages will be added to this instance.
   */
  public function __construct(Runner $runner, HasMessagesInterface $message_bag) {
    $this->setRunner($runner);
    $this->messageBag = $message_bag;
    static::$class_instance_id++;
  }

  /**
   * @param \AKlump\CheckPages\Parts\Test $test
   *
   * @return $this
   *   Self for chaining.
   */
  public function dispatchEventsWith(Test $test): self {
    $suite_group = $test->getSuite()->getGroup() ?: 'http_client';
    $suite_id = $test->getSuite()
      ->id() ?: 'requests' . static::$class_instance_id;
    $suite = new Suite($suite_id, $this->getRunner());
    $suite->setGroup($suite_group);
    // Be careful:  We only pull a few items from the test sent to use because
    // recursion can happen very easily with events.
    $this->testForDispatcher = new Test($test->id(), ['why' => $test->get('why')], $suite);

    return $this;
  }

  /**
   * Set the why for the next request only.
   *
   * Once the request completes the original why value is restored for the next request.
   *
   * @param string $why
   *   A reason for this request, this maps to the "why" key of assertions.
   *
   * @return $this
   *   Self for chaining.
   */
  public function setWhyForNextRequestOnly(string $why): self {
    if ($this->testForDispatcher) {
      $this->stashed['why'] = $this->testForDispatcher->get('why');
      $this->testForDispatcher->set('why', $why);
    }

    return $this;
  }

  public function sendRequest(RequestInterface $request): ResponseInterface {
    $dispatcher = $this->getRunner()->getDispatcher();
    $logger = new HttpMessageLogger($this->getRunner()->getInput(), $this->messageBag);

    // TODO Add support for JS driver.
    $this->driver = new GuzzleDriver();

    // Note: Don't set base URL because that will cause URLs to be printed
    // without the host, which is not what is expected!!!
    $this->driver->setUrl($this->getRunner()->withBaseUrl($request->getUri()));
    $this->driver->setMethod($request->getMethod());
    $this->driver->setBody($request->getBody());
    foreach ($request->getHeaders() as $key => $values) {
      foreach ($values as $value) {
        $this->driver->setHeader($key, $value);
      }
    }

    if ($this->testForDispatcher) {
      $dispatcher->dispatch(new DriverEvent($this->testForDispatcher, $this->driver), Event::REQUEST_CREATED);
      $dispatcher->dispatch(new DriverEvent($this->testForDispatcher, $this->driver), Event::REQUEST_PREPARED);
    }

    // We have to make a new request instance because the first one may not
    // contain an absolute URL, since the baseURL was added after we received
    // the request; a new instance will ensure that we log an absolute URL.
    $logger(new Request($request->getMethod(), (string) $this->driver->getUri(), $request->getHeaders(), $request->getBody()), MessageType::INFO);

    $response = $this->driver->request()->getResponse();
    $logger($response, MessageType::INFO);

    if ($this->testForDispatcher) {
      $dispatcher->dispatch(new DriverEvent($this->testForDispatcher, $this->driver), Event::REQUEST_FINISHED);
      $this->testForDispatcher->set('why', $this->stashed['why'] ?? '');
    }

    return $response;
  }

  /**
   * @return \AKlump\CheckPages\Browser\RequestDriverInterface|null
   *   The driver used by the request.
   */
  public function getDriver(): ?RequestDriverInterface {
    return $this->driver ?? NULL;
  }

}
