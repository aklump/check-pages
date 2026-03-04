<?php

namespace AKlump\CheckPages\Helpers;

use AKlump\CheckPages\Output\Icons;

/**
 * Handles the Icons used by this project.
 *
 * mb_strlen does not accurately count some emojis, so use this class instead.s
 *
 * @see \AKlump\CheckPages\Output\Icons
 */
class GetRealStringLength {

  public function __invoke(string $string): int {

    // Handle certain emojis whose length is not counted correctly by mb_strlen.
    $string = str_replace(Icons::SPYGLASS, '   ', $string);
    $string = str_replace(Icons::NO, '   ', $string);

    return mb_strlen($string);
  }
}
