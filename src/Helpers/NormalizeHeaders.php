<?php

namespace AKlump\CheckPages\Helpers;

use InvalidArgumentException;

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
    $headers = array_change_key_case($headers);
    $headers = array_map(function ($value) {
      if (is_object($value)) {
        throw new InvalidArgumentException('Headers must be an array of strings.');
      }
      if (!is_array($value)) {
        $value = [$value];
      }

      $headers = array_map('strval', $value);
      if ($headers !== array_values($headers)) {
        throw new InvalidArgumentException('Header values must be numerically indexed arrays, not associative arrays.');
      }

      return $headers;
    }, $headers);
    ksort($headers);

    return $headers;
  }
}
