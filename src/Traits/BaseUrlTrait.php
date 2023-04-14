<?php

namespace AKlump\CheckPages\Traits;


trait BaseUrlTrait {

  private $baseUrl;

  /**
   * @return string
   */
  public function getBaseUrl(): string {
    return $this->baseUrl ?? '';
  }

  /**
   * @param string $baseUrl
   *
   * @return void
   */
  public function setBaseUrl(string $baseUrl): void {
    $this->baseUrl = rtrim($baseUrl, '/');
  }

  /**
   * @param string $url
   *   A URL with the base URL stripped.  If the url has a different host then
   *   $url is returned without modification.
   *
   * @return string
   */
  public function withoutBaseUrl(string $url): string {
    $base_url = $this->getBaseUrl();
    if ($base_url && strpos($url, $base_url) !== 0) {
      return $url;
    }

    return substr($url, strlen($base_url));
  }

  public function withBaseUrl(string $url): string {
    $url_scheme = parse_url($url, PHP_URL_SCHEME);
    $base_scheme = parse_url($this->getBaseUrl(), PHP_URL_SCHEME);
    $url_host = parse_url($url, PHP_URL_HOST);
    $base_host = parse_url($this->getBaseUrl(), PHP_URL_HOST);
    $a = $url_scheme . $url_host;
    $b = $base_scheme . $base_host;
    if ($a && $a !== $b) {
      return $url;
    }

    return $this->getBaseUrl() . '/' . ltrim($this->withoutBaseUrl($url), '/');
  }
}
