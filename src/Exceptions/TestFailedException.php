<?php

namespace AKlump\CheckPages\Exceptions;

class TestFailedException extends StopRunnerException {

  public function __construct(array $config, \Exception $exception = NULL) {
    $message = sprintf("Test failed with the following test configuration:\n%s", json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    if ($exception) {
      $message = $exception->getMessage() . PHP_EOL . PHP_EOL . $message;
      parent::__construct($message, $exception->getCode(), $exception);
    }
    parent::__construct($message);
  }

}
