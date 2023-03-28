<?php

namespace AKlump\CheckPages\Browser;

use AKlump\CheckPages\Exceptions\RequestTimedOut;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\RequestOptions;

class GuzzleDriver extends RequestDriver {

  /**
   * {@inheritdoc}
   */
  public function request(array $assertions = NULL): RequestDriverInterface {
    try {
      $this->response = NULL;
      $client = $this->getClient([
        'timeout' => $this->getRequestTimeout(),
        'headers' => $this->getHeaders(),
        // @link http://docs.guzzlephp.org/en/stable/faq.html#how-can-i-track-redirected-requests
        RequestOptions::ALLOW_REDIRECTS => [
          'max' => static::MAX_REDIRECTS,        // allow at most 10 redirects.
          'strict' => TRUE,      // use "strict" RFC compliant redirects.
          'referer' => TRUE,      // add a Referer header
          // Don't need to do this because we use
          // \AKlump\CheckPages\Service\RequestHistory for that instead.
          'track_redirects' => FALSE,
        ],
      ]);
      $this->response = $client->request($this->method, $this->getUrl(), ['body' => $this->body]);
    }
    catch (BadResponseException $exception) {
      // Exception when an HTTP error occurs (4xx or 5xx error)
      $this->response = $exception->getResponse();
    }
    catch (TransferException $exception) {
      $message = $exception->getMessage();
      if (preg_match('/: (Operation timed out after \d+ .+) with/', $message, $matches)) {
        $message = $matches[1] . '.';
      }
      throw new RequestTimedOut($message, $exception->getCode(), $exception);
    }

    return $this;
  }

}
