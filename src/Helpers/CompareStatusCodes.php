<?php

namespace AKlump\CheckPages\Helpers;

/**
 * Compare two status codes, returning -1, 0, or 1
 *
 * Codes can be in these forms: 302, '302' or '3xx'
 */
class CompareStatusCodes {

  public function __invoke($a, $b) {
    if ($a === $b) {
      return 0;
    }

    if (is_numeric($a) && is_numeric($b)) {
      return $a < $b ? -1 : 1;
    }
    if (($a && preg_match('#\dxx#i', $a)) || ($b && preg_match('#\dxx#i', $b))) {
      $a = substr($a, 0, 1);
      $b = substr($b, 0, 1);
    }
    if ($a === $b) {
      return 0;
    }

    return $a < $b ? -1 : 1;
  }
}
