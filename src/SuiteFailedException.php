<?php

namespace AKlump\CheckPages;

class SuiteFailedException extends \Exception {

  public function __construct(string $suite, array $results) {
    parent::__construct(sprintf("Test suite \"%s\" failed.", $suite));
  }

}
