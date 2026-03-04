<?php

namespace AKlump\CheckPages\DataStructure;

use AKlump\CheckPages\Helpers\NormalizeHeaders;

/**
 * A helper class to work with content type headers
 */
class HttpHeader implements \Stringable {

  private array $normalized;

  /**
   * @param string $name The case-insensitive header name.
   * @param array|string $content_type_header_value
   */
  public function __construct(string $name, $content_type_header_value) {
    $headers = [$name => $content_type_header_value];
    $this->normalized = (new NormalizeHeaders())($headers);
  }

  public function getName(): string {
    return key($this->normalized);
  }

  public function get(): array {
    return array_filter(array_map(function (string $value) {
      return explode(';', $value, 2)[0] ?? '';
    }, $this->normalized[$this->getName()]));
  }

  public function __toString() {
    return $this->get()[0] ?? '';
  }


}
