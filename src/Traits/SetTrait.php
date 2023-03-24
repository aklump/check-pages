<?php

namespace AKlump\CheckPages\Traits;

use AKlump\CheckPages\Variables;

/**
 * This is for handlers that use "set:"
 */
trait SetTrait {

  /**
   * Handles the setting of a key/value pair.
   *
   * @param \AKlump\CheckPages\Variables $vars
   * @param string $key
   * @param $value
   *
   * @return string
   *   A user feedback message.
   */
  public function setKeyValuePair(Variables $vars, string $key, $value): string {
    $vars->setItem($key, $value);
    $message = '$%s set to "%s"';
    if (is_null($value)) {
      $message = '$%s set to NULL';
    }
    elseif (!is_scalar($value)) {
      $message = '$%s set.';
    }

    return sprintf($message, $key, $value);
  }

}
