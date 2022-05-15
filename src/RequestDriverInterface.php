<?php


namespace AKlump\CheckPages;


use Psr\Http\Message\ResponseInterface;

interface RequestDriverInterface {

  /**
   * Set the url.
   *
   * @param string $url
   *
   * @return $this
   */
  public function setUrl(string $url): self;

  /**
   * Perform the HTTP request.
   *
   * @return \AKlump\CheckPages\RequestDriverInterface
   */
  public function request(): self;

  /**
   * Return the response after fetching.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *
   * @throws \RuntimeException
   *   If request has not yet been called.
   */
  public function getResponse(): ResponseInterface;

  /**
   * Return the final location of the page (after redirects, if any).
   *
   * @return string
   */
  public function getLocation(): string;

  /**
   * Return the initial redirect status code.
   *
   * @return int
   */
  public function getRedirectCode(): int;

  /**
   * Set an HTTP header to be sent with the request.
   *
   * @param string $key
   * @param string $value
   *
   * @return \AKlump\CheckPages\RequestDriverInterface
   *   Self for chaining.
   */
  public function setHeader(string $key, string $value): RequestDriverInterface;

  /**
   * Get the request headers.
   *
   * @return array
   *   The headers to be sent with the request.
   */
  public function getHeaders(): array;

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
   * @return string[] An array of string values as provided for the given
   *    header. If the header does not appear in the message, this method MUST
   *    return an empty array.
   */
  public function getHeader($name);

  /**
   * Get the invalid certificate policy.
   *
   * @return bool
   *    True if the driver should ignore SSL certificate errors.
   */
  public function allowInvalidCertificate(): bool;

}
