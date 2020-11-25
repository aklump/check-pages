<?php

namespace AKlump\CheckPages;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * Non-Javascript response driver.
 */
class GuzzleDriver implements RequestDriverInterface {

  /**
   * @var string
   */
  protected $url;

  /**
   * @var \Psr\Http\Message\ResponseInterface
   */
  protected $response;

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
    $client = new Client([

      // @link http://docs.guzzlephp.org/en/stable/faq.html#how-can-i-track-redirected-requests
      RequestOptions::ALLOW_REDIRECTS => [
        'max' => 10,        // allow at most 10 redirects.
        'strict' => TRUE,      // use "strict" RFC compliant redirects.
        'referer' => TRUE,      // add a Referer header
        'track_redirects' => TRUE,
      ],
    ]);
    try {
      $this->response = $client->get($this->url);
    }
    catch (ClientException $exception) {
      $this->response = $exception->getResponse();
    }

    return $this->response;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocation(): string {
    return array_last($this->response->getHeader('X-Guzzle-Redirect-History'));
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectCode(): int {
    return $this->response->getHeader('X-Guzzle-Redirect-Status-History')[0] ?? 0;
  }

}
