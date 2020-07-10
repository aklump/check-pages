<?php

namespace AKlump\CheckPages;

class SuiteFailedException extends StopRunnerException {

  public function __construct(string $suite, array $results) {
    parent::__construct(sprintf("Test suite \"%s\" failed.", $suite));
  }

}
