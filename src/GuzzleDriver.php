<?php

namespace AKlump\CheckPages;

use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;

class GuzzleDriver extends RequestDriver {

  /**
   * {@inheritdoc}
   */
  public function getResponse(): ResponseInterface {
    $client = $this->getClient([
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
    catch (ClientException $exception) {
      $this->response = $exception->getResponse();
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
