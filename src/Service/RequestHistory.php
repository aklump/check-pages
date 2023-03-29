<?php

namespace AKlump\CheckPages\Service;

/**
 * Captures the request (redirection) history of an URL.
 */
class RequestHistory {

  /**
   * @var string
   */
  private $cookie;

  /** @var string */
  private $body;

  /** @var int */
  private $maxRedirects;

  /**
   * @var string
   */
  private $currentUrl;

  /**
   * @var bool
   */
  private $allowInvalidCert;

  /**
   * @param int $max_redirects
   * @param bool $allow_invalid_cert
   *   True to allow invalid certificates, see also CURLOPT_SSL_VERIFYPEER,
   *   CURLOPT_SSL_VERIFYHOST.
   * @param string $cookie
   *   In the form of "NAME=VALUE"
   */
  public function __construct(int $max_redirects, bool $allow_invalid_cert, string $cookie = '') {
    $this->maxRedirects = $max_redirects;
    $this->cookie = $cookie;
    $this->allowInvalidCert = $allow_invalid_cert;
  }

  /**
   * @param string $url
   *
   * @return array
   *   An array in chronological order from oldest to newest where each value
   *   is an array with the following keys:
   *     - url string The url that was visited.
   *     - status int The status code received.
   *     - location string The url that was presented to the visitor.
   */
  public function __invoke(string $url): array {
    // The Guzzle library at the time of this did not return the location
    // correctly in some cases.  It would do some URL encoding that we did not
    // want.  Therefor, using vanilla curl to retrieve the location value.  And
    // might as well grab the status code at the same time.
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_MAXREDIRS, $this->maxRedirects);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_NOBODY, 1);
    if ($this->allowInvalidCert) {
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    }
    if ($this->cookie) {
      curl_setopt($ch, CURLOPT_COOKIE, $this->cookie);
    }
    $body = curl_exec($ch);
    if (FALSE === $body) {
      $error = curl_error($ch);
      curl_close($ch);
      throw new \RuntimeException(sprintf("Failed to get request history due to CURL error: %s", $error));
    }
    curl_close($ch);
    $response = preg_split('/^[\n\r]+$/im', $body);

    $this->currentUrl = $url;

    return array_values(array_map([
      $this,
      'parseOne',
    ], array_filter($response)));
  }

  private function parseOne(string $entry): array {
    $this->body = trim($entry, "\n\r");
    $response = [
      'url' => $this->currentUrl,
      'status' => 0,
      'location' => NULL,
    ];
    if (preg_match("/^Location:\s+(.+?)\s/mi", $entry, $matches)) {
      $this->currentUrl = $matches[1];
      $response['location'] = $this->addHost($this->currentUrl);
    }

    if (preg_match("/^(HTTPS?).+\s+(\d+)/mi", $entry, $matches)) {
      $response['status'] = intval($matches[2]);
      $response['location'] = $response['location'] ?? $this->addHost($this->currentUrl);
    }

    return $response;
  }

  private function addHost(string $url): string {
    if (preg_match("/^Host:\s+(.+?)\/?\s/mi", $this->body, $matches)) {
      $host = $matches[1];
    }
    if (preg_match("/^HTTPS?/mi", $this->body, $matches)) {
      $protocol = strtolower($matches[0]);
    }
    if (empty($host) || empty($protocol)) {
      return $url;
    }
    $base_uri = sprintf('%s://%s', $protocol, $host);
    if (strpos($url, $base_uri) !== 0) {
      $url = ltrim($url, '/');
      $url = sprintf('%s/%s', $base_uri, $url);
    }

    return $url;
  }

}
