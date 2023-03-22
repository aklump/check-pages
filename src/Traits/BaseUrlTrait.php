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

      $a = parse_url($url) + ['host' => '', 'scheme' => ''];
      $b = parse_url($base_url) + ['host' => '', 'scheme' => ''];
      if ($a['host'] === $b['host'] && $a['scheme'] !== $b['scheme']) {
        throw new \InvalidArgumentException("It looks like you're trying to create a local URL with the wrong HTTPS schema, the base_url and the url need to have the same scheme if they have the same host.");
      }

      return $url;
    }


    return substr($url, strlen($base_url));
  }

  public function withBaseUrl(string $url): string {
    $url_host = parse_url($url, PHP_URL_HOST);
    $base_host = parse_url($this->getBaseUrl(), PHP_URL_HOST);
    if ($url_host && $url_host !== $base_host) {
      return $url;
    }

    return $this->getBaseUrl() . '/' . ltrim($this->withoutBaseUrl($url), '/');
  }
}
