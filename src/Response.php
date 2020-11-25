<?php

namespace AKlump\CheckPages;

use GuzzleHttp\Psr7\MessageTrait;
use Psr\Http\Message\ResponseInterface;

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

  public function __construct(string $body, int $status_code, array $headers) {
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
