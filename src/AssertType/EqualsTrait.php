<?php

namespace AKlump\CheckPages\AssertType;


trait EqualsTrait {
  /**
   * Compare $a to $b in a special "equals" fashion.
   *
   * @param $a
   * @param $b
   *
   * @return bool
   */
  protected function equals($a, $b) {
    if (is_numeric($a) && is_numeric($b)) {
      return $a == $b;
    }

    if (is_null($a)) {
      $a = '';
    }
    if (is_null($b)) {
      $b = '';
    }

    return $a === $b;
  }
}
