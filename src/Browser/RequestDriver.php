<?php

namespace AKlump\CheckPages\Browser;

use AKlump\CheckPages\Response;
use AKlump\CheckPages\Service\RequestHistory;
use AKlump\CheckPages\Traits\BaseUrlTrait;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

/**
 * Non-Javascript response driver.
 */
abstract class RequestDriver implements RequestDriverInterface {

  use BaseUrlTrait;

  /**
   * Maximum number of redirects to allow per request.  Child classes should
   * utilize this value in their
   * \AKlump\CheckPages\Browser\RequestDriverInterface::request()
   * implementation.
   */
  const MAX_REDIRECTS = 10;

  protected $method = 'GET';

  protected $body = '';

  /**
   * @var string
   */
  private $url = '';

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
   * Keep protected so children can set if they want.
   *
   * @var string
   */
  private $location;

  /**
   * Keep protected so children can set if they want.
   *
   * @var int
   */
  private $redirectCode;

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
    $this->location = NULL;
    $this->redirectCode = NULL;

    return $this;
  }

  public function getUrl(): string {
    return $this->url ?? '';
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

  /**
   * {@inheritdoc}
   */
  public function getLocation(): string {
    if (!isset($this->location)) {
      $this->getRequestHistory();
    }

    return $this->location;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectCode(): int {
    if (!isset($this->redirectCode)) {
      $this->getRequestHistory();
    }

    return $this->redirectCode;
  }

  public function getRequestHistory(): array {
    $redirection = new RequestHistory(self::MAX_REDIRECTS);
    $history = $redirection($this->getUrl());
    $this->redirectCode = $history[0]['status'];
    $final_location = $history[count($history) - 1]['location'];
    $this->location = $this->withoutBaseUrl($final_location);

    return $history;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestTimeout(): int {
    return $this->requestTimeout;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse(): ResponseInterface {
    return $this->response ?? new Response('', 0);
  }

}
