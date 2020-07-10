<?php

namespace AKlump\CheckPages;

class TestFailedException extends StopRunnerException {

  public function __construct(array $config) {
    parent::__construct(sprintf("Test failed with the following configuration:\n%s.", json_encode($config, JSON_PRETTY_PRINT)));
  }

}
