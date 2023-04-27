<?php

namespace AKlump\CheckPages;

use AKlump\CheckPages\Browser\GuzzleDriver;
use AKlump\CheckPages\Browser\RequestDriverInterface;
use AKlump\CheckPages\Output\HttpMessageLogger;
use AKlump\CheckPages\Parts\Runner;
use AKlump\Messaging\HasMessagesInterface;
use AKlump\Messaging\MessageType;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HttpClient implements ClientInterface {

  /**
   * @var \AKlump\CheckPages\Parts\Runner
   */
  private $runner;

  /**
   * @var \AKlump\Messaging\HasMessagesInterface
   */
  private $messageBag;

  /**
   * @param \AKlump\CheckPages\Parts\Runner $runner
   * @param \AKlump\Messaging\HasMessagesInterface $message_bag
   *   Request messages will be added to this instance.
   */
  public function __construct(Runner $runner, HasMessagesInterface $message_bag) {
    $this->runner = $runner;
    $this->messageBag = $message_bag;
  }

  public function sendRequest(RequestInterface $request): ResponseInterface {
    $logger = new HttpMessageLogger($this->runner->getInput(), $this->messageBag);

    // TODO Add support for JS driver.
    $this->driver = new GuzzleDriver();

    // Note: Don't set base URL because that will cause URLs to be printed
    // without the host, which is not what is expected!!!
    $this->driver->setUrl($this->runner->withBaseUrl($request->getUri()));
    $this->driver->setMethod($request->getMethod());
    $this->driver->setBody($request->getBody());
    foreach ($request->getHeaders() as $key => $values) {
      foreach ($values as $value) {
        $this->driver->setHeader($key, $value);
      }
    }

    // We have to make a new request instance because the first one may not
    // contain an absolute URL, since the baseURL was added after we received
    // the request; a new instance will ensure that we log an absolute URL.
    $logger(new Request($request->getMethod(), (string) $this->driver->getUri(), $request->getHeaders(), $request->getBody()), MessageType::INFO);

    $response = $this->driver->request()->getResponse();
    $logger($response, MessageType::INFO);

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
