<?php

namespace AKlump\CheckPages\Traits;


use AKlump\CheckPages\Parts\Runner;

trait HasRunnerTrait {

  private $hasRunnerTraitRunner;

  /**
   * @return NULL|\AKlump\CheckPages\Parts\Runner
   */
  public function getRunner(): ?Runner {
    return $this->hasRunnerTraitRunner;
  }

  /**
   * @param mixed $runner
   *
   * @return $this
   *   Self for chaining.
   */
  public function setRunner(Runner $runner) {
    $this->hasRunnerTraitRunner = $runner;

    return $this;
  }


}
