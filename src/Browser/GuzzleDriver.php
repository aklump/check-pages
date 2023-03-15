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
          'max' => static::MAX_REDIRECTS,        // allow at most 10 redirects.
          'strict' => TRUE,      // use "strict" RFC compliant redirects.
          'referer' => TRUE,      // add a Referer header
          // Don't need to do this because we use
          // \AKlump\CheckPages\Service\RequestHistory for that instead.
          'track_redirects' => false,
        ],
      ]);
      $this->response = $client->request($this->method, $this->getUrl(), ['body' => $this->body]);
    }
    catch (BadResponseException $exception) {
      $this->response = $exception->getResponse();
    }
    catch (\Exception $e) {
      throw new StopRunnerException($e->getMessage(), $e->getCode(), $e);
    }

    return $this;
  }

}
