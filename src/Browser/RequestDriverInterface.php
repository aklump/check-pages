<?php


namespace AKlump\CheckPages\Browser;


use Psr\Http\Message\ResponseInterface;

interface RequestDriverInterface {

  public function setBaseUrl(string $base_url): void;

  /**
   * Set the url.
   *
   * @param string $url
   *
   * @return $this
   */
  public function setUrl(string $url): RequestDriverInterface;

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
   * @throws \AKlump\CheckPages\Exceptions\StopRunnerException
   *   If the request fails for any reason.
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
   *
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

  /**
   * Set the maximum seconds to wait for a request.
   *
   * @param int $request_timeout
   *
   * @return \AKlump\CheckPages\RequestDriverInterface
   *   Self for chaining.
   */
  public function setRequestTimeout(int $request_timeout): RequestDriverInterface;

  /**
   * @return int
   *   The number of seconds to wait for the page to fully load before timeout.
   */
  public function getRequestTimeout(): int;

}
