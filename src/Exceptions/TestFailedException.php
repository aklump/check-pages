<?php

namespace AKlump\CheckPages\Exceptions;

class TestFailedException extends StopRunnerException {

  public function __construct(array $config, \Exception $exception = NULL) {
    $message = sprintf("Test failed with the following test configuration:\n%s.", json_encode($config, JSON_PRETTY_PRINT));
    if ($exception) {
      $message = rtrim($exception->getMessage(), '. ') . '. ' . $message;
      parent::__construct($message, $exception->getCode(), $exception);
    }
    parent::__construct($message);
  }

}
