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

  public function getLines(): array {
    return $this->normalized[$this->getName()] ?? [];
  }

  /**
   * @deprecated Use getLines()
   * @return string[]
   */
  public function get(): array {
    return $this->getLines();
  }

  public function __toString() {
    return $this->getLines()[0] ?? '';
  }


}
