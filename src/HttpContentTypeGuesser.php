<?php

namespace AKlump\CheckPages;

/**
 * Use this class with care.  See link below.
 *
 * @link https://www.coalfire.com/the-coalfire-blog/mime-sniffing-in-browsers-and-the-security
 */
final class HttpContentTypeGuesser {

  /**
   * @link https://mattryall.net/blog/default-content-type
   */
  const FALLBACK = 'application/octet-stream';

  /**
   * @var string
   */
  private $content;

  /**
   * Guess a mime type from content.
   *
   * @param string $content
   *   HTTP request/response body to guess from.
   *
   * @return string
   *   The best-guess mime type.
   */
  public function guessType(string $content): string {
    $this->content = $content;
    if ($this->isJSON()) {
      return 'application/json';
    }
    if ($this->isXML()) {
      return 'application/xml';
    }
    if ($this->isFormURLEncoded()) {
      return 'application/x-www-form-urlencoded';
    }

    return self::FALLBACK;
  }

  private function isJSON() {
    if (NULL === $this->content) {
      return TRUE;
    }

    return json_decode($this->content) !== NULL;
  }

  private function isXML() {
    return FALSE !== simplexml_load_string($this->content);
  }

  private function isFormURLEncoded() {
    $query = trim($this->content);
    $parsed = parse_url($query);

    return !empty($query) && is_array($parsed) && count($parsed) > 0;
  }

}
