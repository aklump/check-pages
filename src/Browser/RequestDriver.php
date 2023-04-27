<?php

namespace AKlump\CheckPages\Browser;

use AKlump\CheckPages\Response;
use AKlump\CheckPages\Service\RequestHistory;
use AKlump\CheckPages\Traits\BaseUrlTrait;
use AKlump\Messaging\MessengerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

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
  protected $requestTimeoutInSeconds = 20;

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
   * @var \AKlump\Messaging\MessengerInterface
   */
  private $messenger;

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
    if (!preg_match('/^http/i', $url)) {
      throw new \InvalidArgumentException(sprintf('$url must be an absolute URL; "%s" is not', $url));
    }
    $this->url = $url;
    $this->location = NULL;
    $this->redirectCode = NULL;

    return $this;
  }

  /**
   * @return string
   *
   * @deprecated Use getUri()
   */
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
   * {@inheritdoc}
   */
  public function getHeader($name) {
    $headers = array_change_key_case($this->getHeaders());
    if (empty($headers[$name])) {
      return [];
    }

    return [$headers[$name]];
  }

  /**
   * {@inheritdoc}
   */
  public function hasHeader(string $name) {
    return array_key_exists($name, array_change_key_case($this->getHeaders()));
  }

  /**
   * {@inheritdoc}
   */
  public function getBody() {
    // TODO The interface says this has to return as a StreamResource, how to?
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

  public function __toString() {
    return $this->getBody();
  }

  public function getMessenger(): ?MessengerInterface {
    return $this->messenger ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setRequestTimeout(int $timeout_in_seconds): RequestDriverInterface {
    $this->requestTimeoutInSeconds = $timeout_in_seconds;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocation(): string {
    if (!isset($this->location)) {
      $this->getRequestHistory();
    }

    return $this->location ?? '';
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
    $redirection = new RequestHistory(self::MAX_REDIRECTS, $this->allowInvalidCertificate(), $this->getHeader('cookie')[0] ?? '');
    $history = $redirection($this->getUrl());
    $this->redirectCode = $history[0]['status'] ?? NULL;
    $final_location = $history[count($history) - 1]['location'] ?? NULL;
    if ($final_location) {
      $this->location = $this->withoutBaseUrl($final_location);
    }

    return $history;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestTimeout(): int {
    return $this->requestTimeoutInSeconds;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse(): ResponseInterface {
    return $this->response ?? new Response('', 0);
  }

  public function setMessenger(MessengerInterface $messenger): RequestDriverInterface {
    $this->messenger = $messenger;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProtocolVersion() {
    // TODO: Implement getProtocolVersion() method.
  }

  public function withProtocolVersion(string $version) {
    // TODO: Implement withProtocolVersion() method.
  }

  public function getHeaderLine(string $name) {
    // TODO: Implement getHeaderLine() method.
  }

  public function withHeader(string $name, $value) {
    // TODO: Implement withHeader() method.
  }

  public function withAddedHeader(string $name, $value) {
    // TODO: Implement withAddedHeader() method.
  }

  public function withoutHeader(string $name) {
    // TODO: Implement withoutHeader() method.
  }

  public function withBody(\Psr\Http\Message\StreamInterface $body) {
    // TODO: Implement withBody() method.
  }

  public function getRequestTarget() {
    // TODO: Implement getRequestTarget() method.
  }

  public function withRequestTarget(string $requestTarget) {
    // TODO: Implement withRequestTarget() method.
  }

  /**
   * {@inheritdoc}
   */
  public function getMethod() {
    return $this->method ?? '';
  }

  public function withMethod(string $method) {
    // TODO: Implement withMethod() method.
  }

  /**
   * {@inheritdoc}
   */
  public function getUri() {
    return new Uri($this->url ?? '');
  }

  public function withUri(UriInterface $uri, bool $preserveHost = FALSE) {
    // TODO: Implement withUri() method.
  }

}
