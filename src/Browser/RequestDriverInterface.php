<?php


namespace AKlump\CheckPages\Browser;


use AKlump\Messaging\MessengerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface RequestDriverInterface extends RequestInterface {

  public function setBaseUrl(string $base_url): void;

  /**
   * Set the absolute URL of the request.
   *
   * @param string $absolute_url
   *
   * @return $this
   */
  public function setUrl(string $absolute_url): RequestDriverInterface;

  /**
   * @return string
   *   The absolute URL of the request.
   *
   * @deprecated Use (string) self::getUri() instead.
   */
  public function getUrl(): string;

  /**
   * Perform the HTTP request.
   *
   * @param \AKlump\CheckPages\Service\Assertion[] $assertions
   *    Optional.  One or more assertions that will be used to delay the
   *    results.  This can be used to wait for certain AJAX objects to load by
   *    asserting that such and such element is present.
   *
   * @return \AKlump\CheckPages\Browser\RequestDriverInterface
   *   Self for chaining.
   *
   * @throws \AKlump\CheckPages\Exceptions\RequestTimedOut
   * @throws \RuntimeException
   *
   * @see \AKlump\CheckPages\Browser\RequestDriverInterface::getResponse();
   */
  public function request(array $assertions = NULL): RequestDriverInterface;

  /**
   * Return the response after fetching.
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  public function getResponse(): ResponseInterface;

  /**
   * Return the final location of the page (after redirects, if any).
   *
   * @return string
   *   This will be relative if it contains the configured "base_url", otherwise
   *   it will be absolute.  You should use this same form when setting the
   *   "location" value in tests.
   */
  public function getLocation(): string;

  /**
   * Return the initial redirect status code.
   *
   * Pay attention because if there is more than one redirect, this will give
   * you the first redirect code encountered.  This is opposite of
   * \AKlump\CheckPages\Browser\RequestDriverInterface::getLocation() which
   * returns the final destination URL.
   *
   * @return int
   */
  public function getRedirectCode(): int;

  /**
   * Get the invalid certificate policy.
   *
   * @return bool
   *    True if the driver should ignore SSL certificate errors.
   */
  public function allowInvalidCertificate(): bool;

  /**
   * Set the maximum seconds to wait for a request.
   *
   * @param int $timeout_in_seconds
   *
   * @return \AKlump\CheckPages\RequestDriverInterface
   *   Self for chaining.
   */
  public function setRequestTimeout(int $timeout_in_seconds): RequestDriverInterface;

  /**
   * @return int
   *   Total seconds to wait before cancelling request.
   */
  public function getRequestTimeout(): int;

  public function getMessenger(): ?MessengerInterface;

  public function setMessenger(MessengerInterface $messenger): RequestDriverInterface;

}
