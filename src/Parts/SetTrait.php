<?php

namespace AKlump\CheckPages\Parts;

use AKlump\CheckPages\Variables;

trait SetTrait {

  /**
   * Handles the setting of a key/value pair.
   *
   * @param \AKlump\CheckPages\Variables $vars
   * @param string $key
   * @param $value
   *
   * @return string
   *   Feedback suitable for \Symfony\Component\Console\Output\OutputInterface::writeln.
   */
  protected function setKeyValuePair(Variables $vars, string $key, $value): string {
    $vars->setItem($key, $value);
    $message = '${%s} set to "%s"';
    if (!is_scalar($value)) {
      $message = '${%s} set.';
    }

    return sprintf($message, $key, $value);
  }

}
