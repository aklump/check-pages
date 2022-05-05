<?php

namespace AKlump\CheckPages;

use GuzzleHttp\Psr7\MessageTrait;
use Psr\Http\Message\ResponseInterface;

/**
 * Generic response to use to handle odd situations, like 500 responses etc.
 */
class Response implements ResponseInterface {

  use MessageTrait;

  /**
   * @var string
   */
  protected $body;

  /**
   * @var int
   */
  protected $statusCode;

  /**
   * @param string $body
   * @param int $status_code
   * @param array $headers
   *   An array of key values, each value may be a string or an array.  However,
   *   be advised that a string may not contain any \n characters; rather it
   *   should be split on \n as an array.
   */
  public function __construct(string $body, int $status_code, array $headers = []) {
    $this->body = $body;
    $this->statusCode = $status_code;
    $this->setHeaders($headers);
  }

  /**
   * @inheritDoc
   */
  public function getBody() {
    return $this->body;
  }

  /**
   * @inheritDoc
   */
  public function getStatusCode() {
    return $this->statusCode;
  }

  public function withStatus($code, $reasonPhrase = '') {
    // TODO: Implement withStatus() method.
  }

  public function getReasonPhrase() {
    // TODO: Implement getReasonPhrase() method.
  }
}
