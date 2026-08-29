<?php

namespace AKlump\CheckPages\Event;

use AKlump\CheckPages\Parts\Runner;

class RunnerEvent implements RunnerEventInterface {

  protected Runner $runner;

  public function __construct(Runner $runner) {
    $this->runner = $runner;
  }

  public function getRunner(): Runner {
    return $this->runner;
  }

}
