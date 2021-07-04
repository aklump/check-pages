<?php


namespace AKlump\CheckPages;


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
   * Get the request response.
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  public function getResponse(): \Psr\Http\Message\ResponseInterface;

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

}
