<?php

namespace AKlump\CheckPages\Browser;

use AKlump\CheckPages\Response;
use AKlump\CheckPages\Service\RequestHistory;
use AKlump\CheckPages\Traits\BaseUrlTrait;
use AKlump\Messaging\MessengerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Utils;
use InvalidArgumentException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
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

  protected string $method = 'GET';

  protected string $body = '';

  /**
   * @var string
   */
  private string $url = '';

  /**
   * @var \Psr\Http\Message\ResponseInterface|null
   */
  protected  $response;

  protected array $headers;

  protected int $requestTimeoutInSeconds = 20;

  /**
   * Keep protected so children can set if they want.
   *
   * @var string|null
   */
  private $location;

  /**
   * Keep protected so children can set if they want.
   *
   * @var int|null
   */
  private $redirectCode;

  private MessengerInterface $messenger;

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
   * @return self
   *   Self for chaining.
   */
  public function setMethod(string $method): self {
    $this->method = $method;

    return $this;
  }

  /**
   * @param string $body
   *
   * @return self
   *   Self for chaining.
   */
  public function setBody(string $body): self {
    $this->body = $body;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setUrl(string $absolute_url): RequestDriverInterface {
    if (!preg_match('/^http/i', $absolute_url)) {
      throw new InvalidArgumentException(sprintf('$absolute_url must be an absolute URL; "%s" is not', $absolute_url));
    }
    $this->url = $absolute_url;
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

  public function getClient(array $options = []): Client {
    return new Client($options + ['verify' => !$this->allowInvalidCertificate()]);
  }

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
  public function getHeader($name): array {
    $headers = array_change_key_case($this->getHeaders());
    if (empty($headers[$name])) {
      return [];
    }

    return [$headers[$name]];
  }

  /**
   * {@inheritdoc}
   */
  public function hasHeader(string $name): bool {
    return array_key_exists($name, array_change_key_case($this->getHeaders()));
  }

  /**
   * {@inheritdoc}
   */
  public function getBody(): StreamInterface {
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

    $string = implode(PHP_EOL, $string) . PHP_EOL;
    return Utils::streamFor($string);
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
  public function getProtocolVersion(): string {
    // TODO: Implement getProtocolVersion() method.
  }

  public function withProtocolVersion(string $version): MessageInterface {
    // TODO: Implement withProtocolVersion() method.
  }

  public function getHeaderLine(string $name): string {
    // TODO: Implement getHeaderLine() method.
  }

  public function withHeader(string $name, $value): MessageInterface {
    // TODO: Implement withHeader() method.
  }

  public function withAddedHeader(string $name, $value): MessageInterface {
    // TODO: Implement withAddedHeader() method.
  }

  public function withoutHeader(string $name): MessageInterface {
    // TODO: Implement withoutHeader() method.
  }

  public function withBody(StreamInterface $body): MessageInterface {
    // TODO: Implement withBody() method.
  }

  public function getRequestTarget(): string {
    // TODO: Implement getRequestTarget() method.
  }

  public function withRequestTarget(string $requestTarget): RequestInterface {
    // TODO: Implement withRequestTarget() method.
  }

  /**
   * {@inheritdoc}
   */
  public function getMethod(): string {
    return $this->method ?? '';
  }

  public function withMethod(string $method): RequestInterface {
    // TODO: Implement withMethod() method.
  }

  /**
   * {@inheritdoc}
   */
  public function getUri(): UriInterface {
    return new Uri($this->url ?? '');
  }

  public function withUri(UriInterface $uri, bool $preserveHost = FALSE): RequestInterface {
    // TODO: Implement withUri() method.
  }

}
