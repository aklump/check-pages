<?php

namespace AKlump\CheckPages\Exceptions;

class StopRunnerException extends \Exception {

  public function __construct(
    $message = "Testing failed due to an unspecified error.",
    $code = 0,
    $previous = NULL
  ) {
    return parent::__construct($message, $code, $previous);
  }
}
