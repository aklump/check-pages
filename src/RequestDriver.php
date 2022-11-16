<?php

namespace AKlump\CheckPages;

use GuzzleHttp\Client;

/**
 * Non-Javascript response driver.
 */
abstract class RequestDriver implements RequestDriverInterface {

  protected $method = 'GET';

  protected $body = '';

  /**
   * @var string
   */
  protected $url = '';

  /**
   * @var \Psr\Http\Message\ResponseInterface
   */
  protected $response;

  protected $headers;

  /**
   * @var int
   */
  protected $requestTimeout = 20;

  /**
   * {@inheritdoc}
   */
  public function allowInvalidCertificate(): bool {
    // TODO Move this to config?
    return TRUE;
  }

  /**
   * @param string $method
   *
   * @return
   *   Self for chaining.
   */
  public function setMethod(string $method): self {
    $this->method = $method;

    return $this;
  }

  /**
   * @param string $body
   *
   * @return
   *   Self for chaining.
   */
  public function setBody(string $body): self {
    $this->body = $body;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setUrl(string $url): RequestDriverInterface {
    $this->url = $url;

    return $this;
  }

  public function getUrl(): string {
    return $this->url;
  }

  public function getClient(array $options = []) {
    return new Client($options + ['verify' => !$this->allowInvalidCertificate()]);
  }

  /**
   * {@inheritdoc}
   */
  public function setHeader(string $key, string $value): RequestDriverInterface {
    $this->headers[$key] = $value;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeaders(): array {
    return $this->headers ?? [];
  }

  /**
   * Retrieves a message header value by the given case-insensitive name.
   *
   * This method returns an array of all the header values of the given
   * case-insensitive header name.
   *
   * If the header does not appear in the message, this method MUST return an
   * empty array.
   *
   * @param string $name Case-insensitive header field name.
   *
   * @return string[] An array of string values as provided for the given
   *    header. If the header does not appear in the message, this method MUST
   *    return an empty array.
   */
  public function getHeader($name) {
    $headers = array_change_key_case($this->getHeaders(), CASE_LOWER);
    if (empty($headers['content-type'])) {
      return [];
    }

    return [$headers['content-type']];
  }

  public function __toString() {
    $string = [];
    if ($this->body) {
      $body = $this->body;
      $json = json_decode($body);
      if (!is_null($json)) {
        $body = json_encode($json, JSON_PRETTY_PRINT);
      }
      $string[] = $body;
    }

    return implode(PHP_EOL, $string) . PHP_EOL;
  }

  /**
   * {@inheritdoc}
   */
  public function setRequestTimeout(int $request_timeout): RequestDriverInterface {
    $this->requestTimeout = $request_timeout;
    return $this;
  }

  public function getRequestTimeout(): int {
    return $this->requestTimeout;
  }

}
