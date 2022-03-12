<?php

namespace AKlump\CheckPages\Event;

use AKlump\CheckPages\Parts\Runner;

class RunnerEvent implements RunnerEventInterface {

  /**
   * @var \AKlump\CheckPages\Parts\Runner
   */
  protected $runner;

  public function __construct(Runner $runner) {
    $this->runner = $runner;
  }

  public function getRunner(): Runner {
    return $this->runner;
  }
}
