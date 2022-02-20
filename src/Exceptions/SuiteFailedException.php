<?php

namespace AKlump\CheckPages\Exceptions;

class SuiteFailedException extends StopRunnerException {

  public function __construct(string $suite) {
    parent::__construct(sprintf("Test suite \"%s\" failed.", $suite));
  }

}
