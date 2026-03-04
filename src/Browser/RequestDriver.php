<?php

namespace AKlump\CheckPages\Browser;

use AKlump\CheckPages\Helpers\NormalizeHeaders;
use AKlump\CheckPages\Response;
use AKlump\CheckPages\Service\RequestHistory;
use AKlump\CheckPages\Traits\BaseUrlTrait;
use AKlump\CheckPages\Traits\HasTestTrait;
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
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Non-Javascript response driver.
 */
abstract class RequestDriver implements RequestDriverInterface {

  use BaseUrlTrait;
  use HasTestTrait;

  /**
   * Maximum number of redirects to allow per request.  Child classes should
   * utilize this value in their
   * \AKlump\CheckPages\Browser\RequestDriverInterface::request()
   * implementation.
   */
  const MAX_REDIRECTS = 10;

  private EventDispatcher $dispatcher;

  protected string $method = 'GET';

  protected string $body = '';

  /**
   * @var string
   */
  private string $url = '';

  /**
   * @var \Psr\Http\Message\ResponseInterface|null
   */
  protected $response;

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
   * @param \Symfony\Component\EventDispatcher\EventDispatcher $dispatcher
   */
  public function __construct(EventDispatcher $dispatcher) {
    $this->dispatcher = $dispatcher;
  }

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

  /**
   * Mutable equivalent of PSR-7 withHeader(): replace the header entirely.
   */
  public function setHeader(string $name, $value): RequestDriverInterface {
    if (is_string($value)) {
      @trigger_error(sprintf('%s(string $value) is deprecated in version 0.23.3 and will not be supported in future versions. Use %s(array $value) instead.', __METHOD__, __METHOD__), E_USER_DEPRECATED);
    }
    $normalized = (new NormalizeHeaders())([$name => $value]);
    $normalizedName = (string) key($normalized);
    $normalizedValues = (array) current($normalized);

    $this->headers ??= [];

    // Remove any existing header matching $name (case-insensitive).
    foreach (array_keys($this->headers) as $existingName) {
      if (strcasecmp($existingName, $name) === 0) {
        unset($this->headers[$existingName]);
      }
    }

    $this->headers[$normalizedName] = array_values($normalizedValues);

    return $this;
  }

  /**
   * Mutable equivalent of PSR-7 withAddedHeader(): append values to any existing.
   */
  public function addHeader(string $name, $value): RequestDriverInterface {
    $normalized = (new NormalizeHeaders())([$name => $value]);
    $normalizedName = (string) key($normalized);
    $normalizedValues = (array) current($normalized);

    $this->headers ??= [];

    // Append to an existing key matching case-insensitively (preserve casing).
    $targetKey = $normalizedName;
    foreach (array_keys($this->headers) as $existingName) {
      if (strcasecmp($existingName, $normalizedName) === 0) {
        $targetKey = $existingName;
        break;
      }
    }

    $existingValues = isset($this->headers[$targetKey]) ? (array) $this->headers[$targetKey] : [];
    $this->headers[$targetKey] = array_values(array_merge($existingValues, $normalizedValues));

    return $this;
  }

  /**
   * Mutable equivalent of PSR-7 withoutHeader(): remove the header.
   */
  public function unsetHeader(string $name): RequestDriverInterface {
    $this->headers ??= [];

    foreach (array_keys($this->headers) as $existingName) {
      if (strcasecmp($existingName, $name) === 0) {
        unset($this->headers[$existingName]);
      }
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeaders(): array {
    $headers = $this->headers ?? [];
    // TODO Change global to DI?
    global $container;
    $test = $this->getTest();
    if ($container && $test) {
      $headers['x-testing-framework'] = [
        $container->get('x-testing-framework')(),
      ];
      $headers['x-testing-token'] = [
        $test->getRunner()->get('testing_token'),
      ];
    }

    return $headers;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeader($name): array {
    $headers = array_change_key_case($this->getHeaders());
    $name = strtolower($name);
    if (empty($headers[$name])) {
      return [];
    }

    return $headers[$name];
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
    return $this->protocolVersion ?? '1.1';
  }

  public function withProtocolVersion(string $version): MessageInterface {
    $clone = clone $this;
    $clone->protocolVersion = $version;

    return $clone;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeaderLine(string $name): string {
    $header = $this->getHeader($name);
    if (empty($header)) {
      return '';
    }

    return implode(',', $header);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated For CheckPages driver mutation, use setHeader()/addHeader()/unsetHeader().
   * This method exists for PSR-7 compliance and returns a clone.
   * @see \AKlump\CheckPages\Browser\RequestDriverInterface::setHeader()
   */
  public function withHeader(string $name, $value): MessageInterface {
    $normalized = (new NormalizeHeaders())([$name => $value]);
    $normalizedName = (string) key($normalized);
    $normalizedValues = (array) current($normalized);

    $clone = clone $this;

    $headers = $clone->headers ?? [];

    // Remove any existing header matching $name (case-insensitive).
    foreach (array_keys($headers) as $existingName) {
      if (strcasecmp($existingName, $name) === 0) {
        unset($headers[$existingName]);
      }
    }

    $headers[$normalizedName] = array_values($normalizedValues);
    $clone->headers = $headers;

    return $clone;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated For CheckPages driver mutation, use setHeader()/addHeader()/unsetHeader().
   * This method exists for PSR-7 compliance and returns a clone.
   * @see \AKlump\CheckPages\Browser\RequestDriverInterface::addHeader()
   */
  public function withAddedHeader(string $name, $value): MessageInterface {
    $normalized = (new NormalizeHeaders())([$name => $value]);
    $normalizedName = (string) key($normalized);
    $normalizedValues = (array) current($normalized);

    $clone = clone $this;

    $headers = $clone->headers ?? [];

    // Find an existing header key that matches case-insensitively, so we append
    // to the originally-stored key (preserves current casing choice).
    $targetKey = $normalizedName;
    foreach (array_keys($headers) as $existingName) {
      if (strcasecmp($existingName, $normalizedName) === 0) {
        $targetKey = $existingName;
        break;
      }
    }

    $existingValues = isset($headers[$targetKey]) ? (array) $headers[$targetKey] : [];
    $headers[$targetKey] = array_values(array_merge($existingValues, $normalizedValues));

    $clone->headers = $headers;

    return $clone;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated For CheckPages driver mutation, use setHeader()/addHeader()/unsetHeader().
   * This method exists for PSR-7 compliance and returns a clone.
   * @see \AKlump\CheckPages\Browser\RequestDriverInterface::unsetHeader()
   */
  public function withoutHeader(string $name): MessageInterface {
    $clone = clone $this;

    $headers = $clone->headers ?? [];
    foreach (array_keys($headers) as $existingName) {
      if (strcasecmp($existingName, $name) === 0) {
        unset($headers[$existingName]);
      }
    }
    $clone->headers = $headers;

    return $clone;
  }

  public function withBody(StreamInterface $body): MessageInterface {
    $clone = clone $this;
    $clone->body = $body;

    return $clone;
  }

  public function getRequestTarget(): string {
    return (string) $this->getUri();
  }

  public function withRequestTarget(string $requestTarget): RequestInterface {
    $clone = clone $this;
    $clone->setUrl($requestTarget);

    return $clone;
  }

  /**
   * {@inheritdoc}
   */
  public function getMethod(): string {
    return $this->method ?? '';
  }

  public function withMethod(string $method): RequestInterface {
    $clone = clone $this;
    $clone->setMethod($method);

    return $clone;
  }

  /**
   * {@inheritdoc}
   */
  public function getUri(): UriInterface {
    return new Uri($this->url ?? '');
  }

  public function withUri(UriInterface $uri, bool $preserveHost = FALSE): RequestInterface {
    $clone = clone $this;
    $clone->setUrl((string) $uri);

    return $clone;
  }

  public function getDispatcher(): EventDispatcher {
    return $this->dispatcher;
  }

}
