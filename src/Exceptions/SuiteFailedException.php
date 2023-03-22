<?php

namespace AKlump\CheckPages\Exceptions;

use AKlump\CheckPages\Parts\Suite;

class SuiteFailedException extends StopRunnerException {

  public function __construct(Suite $suite, $message_or_exception = NULL) {
    $config = $suite->getConfig();
    $message = sprintf("Suite failed with the following configuration:\n%s", json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    if ($message_or_exception instanceof \Exception) {
      $message = $message_or_exception->getMessage() . PHP_EOL . PHP_EOL . $message;
      parent::__construct($message, $message_or_exception->getCode(), $message_or_exception);
    }
    else {
      parent::__construct($message_or_exception . PHP_EOL . PHP_EOL . $message);
    }
  }

}
