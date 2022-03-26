<?php

namespace AKlump\CheckPages\Exceptions;

/**
 * Throw when a path cannot be resolved.
 */
class UnresolvablePathException extends \InvalidArgumentException {

  public function __construct(string $path, string $message = NULL) {
    if (is_null($message)) {
      $message = sprintf('This path cannot be resolved: "%s"', $path);
    }

    return parent::__construct($message);
  }

}
