<?php

namespace AKlump\CheckPages\Exceptions;

/**
 * You probably don't want to throw this, maybe TestFailedException instead.
 * This should never be thrown once a test has started and before it has
 * stopped.
 */
class StopRunnerException extends \Exception {

  public function __construct(
    $message = "Testing failed due to an unspecified error.",
    $code = 0,
    $previous = NULL
  ) {
    return parent::__construct($message, $code, $previous);
  }
}
