<?php

namespace AKlump\CheckPages;

use GuzzleHttp\Exception\BadResponseException;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\RequestOptions;

class GuzzleDriver extends RequestDriver {

  /**
   * {@inheritdoc}
   */
  public function request(): RequestDriverInterface {
    $client = $this->getClient([
      'timeout' => static::SERVER_TIMEOUT,
      'headers' => $this->headers,
      // @link http://docs.guzzlephp.org/en/stable/faq.html#how-can-i-track-redirected-requests
      RequestOptions::ALLOW_REDIRECTS => [
        'max' => 10,        // allow at most 10 redirects.
        'strict' => TRUE,      // use "strict" RFC compliant redirects.
        'referer' => TRUE,      // add a Referer header
        'track_redirects' => TRUE,
      ],
    ]);
    try {
      $this->response = $client->request($this->method, $this->url, ['body' => $this->body]);
    }
    catch (BadResponseException $exception) {
      $this->response = $exception->getResponse();
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse(): ResponseInterface {
    if (empty($this->response)) {
      throw new \RuntimeException(sprintf('%s::request() has not been called; there is no response to get.', get_class($this)));
    }

    return $this->response;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocation(): string {
    return (string) array_last($this->response->getHeader('X-Guzzle-Redirect-History') ?? []);
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectCode(): int {
    return (int) ($this->response->getHeader('X-Guzzle-Redirect-Status-History')[0] ?? 0);
  }

}
