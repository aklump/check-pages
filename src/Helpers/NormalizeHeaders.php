<?php

namespace AKlump\CheckPages\Helpers;

class NormalizeHeaders {

  /**
   * Normalize an array of HTTP headers so that:
   * - all keys are lower-case.
   * - all values are arrays.
   *
   * @param array $headers
   *
   * @return array
   */
  public function __invoke(array $headers): array {
    $headers = array_combine(array_map('strtolower', array_keys($headers)), array_map(function ($value) {
      if (is_string($value)) {
        return [$value];
      }

      return $value;
    }, $headers));
    ksort($headers);

    return $headers;
  }
}
