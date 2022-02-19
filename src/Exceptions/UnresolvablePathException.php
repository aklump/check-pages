<?php

namespace AKlump\CheckPages\Exceptions;

/**
 * Throw when a path cannot be resolved.
 */
class UnresolvablePathException extends \InvalidArgumentException {

  public function __construct($path) {
    $message = sprintf('This path cannot be resolved: "%s"', $path);

    return parent::__construct($message);
  }

}
