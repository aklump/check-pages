<?php

namespace AKlump\CheckPages;

use Behat\Mink\Mink;
use Behat\Mink\Session;
use DMore\ChromeDriver\ChromeDriver as BehatChromeDriver;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * Chrome-based driver with javascript support.
 */
class ChromeDriver implements RequestDriverInterface {

  protected $url;

  protected $response;

  protected $location;

  protected $mink;

  /**
   * ChromeDriver constructor.
   *
   * @param string $base_url
   */
  public function __construct(string $base_url) {
    $this->mink = new Mink([
      'browser' => new Session(new BehatChromeDriver('http://localhost:9222', NULL, $base_url)),
    ]);
    $this->mink->setDefaultSessionName('browser');
  }

  /**
   * {@inheritdoc}
   */
  public function setUrl(string $url): RequestDriverInterface {
    $this->url = $url;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse(): ResponseInterface {
    $session = $this->mink->getSession();
    $session->visit($this->url);
    $this->location = $session->getCurrentUrl();

    $this->response = new Response(
      $session->getPage()->getContent(),
      $session->getStatusCode(),
      $session->getResponseHeaders()
    );

    return $this->response;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocation(): string {
    return $this->location;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectCode(): int {
    // TODO Is there a way to determine the redirect code using Mink?
    try {
      $client = new Client([RequestOptions::ALLOW_REDIRECTS => FALSE]);
      $response = $client->get($this->url);
    }
    catch (ClientException $exception) {
      $response = $exception->getResponse();
    }

    return $response->getStatusCode();
  }

}
