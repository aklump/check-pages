<?php

namespace AKlump\CheckPages\Browser;

use AKlump\CheckPages\Exceptions\StopRunnerException;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\RequestOptions;

class GuzzleDriver extends RequestDriver {

  /**
   * {@inheritdoc}
   */
  public function request(array $assertions = NULL): RequestDriverInterface {
    try {
      $this->response = NULL;
      $client = $this->getClient([
        'timeout' => $this->requestTimeout,
        'headers' => $this->headers,
        // @link http://docs.guzzlephp.org/en/stable/faq.html#how-can-i-track-redirected-requests
        RequestOptions::ALLOW_REDIRECTS => [
          'max' => 10,        // allow at most 10 redirects.
          'strict' => TRUE,      // use "strict" RFC compliant redirects.
          'referer' => TRUE,      // add a Referer header
          'track_redirects' => TRUE,
        ],
      ]);
      $this->response = $client->request($this->method, $this->getUrl(), ['body' => $this->body]);
      $this->location = array_values($this->response->getHeader('X-Guzzle-Redirect-History'))[0] ?? NULL;
    }
    catch (BadResponseException $exception) {
      $this->response = $exception->getResponse();
    }
    catch (\Exception $e) {
      throw new StopRunnerException($e->getMessage(), $e->getCode(), $e);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectCode(): int {
    return (int) ($this->response->getHeader('X-Guzzle-Redirect-Status-History')[0] ?? 0);
  }

}
