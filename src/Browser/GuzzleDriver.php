<?php

namespace AKlump\CheckPages\Browser;

use AKlump\CheckPages\Exceptions\RequestTimedOut;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
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
      $attempts = 1;
      $max_attempts = 1;
      $is_successful = false;
      while (!$is_successful && $attempts <= $max_attempts) {
        try {
          $this->response = $client->request($this->method, $this->getUri(), ['body' => $this->body]);
          $is_successful = TRUE;
        }
        catch (ConnectException $exception) {
          // TODO Figure out what to do to fix this.  I've tried sleep() and I've tried a new $client instance; they both failed.  It seems to happen after a bunch of requests (99), is it time  (I don't think it's time, because I put a sleep(1) between requests and it still stopped after the same number of requests, event though T was longer), or memory, that crashes it?  I THINK IT'S MEMORY.
          ++$attempts;
        }
      }
      if (!empty($exception)) {
        throw $exception;
      }
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
