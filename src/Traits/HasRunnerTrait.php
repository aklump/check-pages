<?php

namespace AKlump\CheckPages\Traits;


use AKlump\CheckPages\Parts\Runner;

trait HasRunnerTrait {

  private $hasRunnerTraitRunner;

  /**
   * @return \AKlump\CheckPages\Parts\Runner
   */
  public function getRunner(): Runner {
    return $this->hasRunnerTraitRunner;
  }

  /**
   * @param mixed $runner
   *
   * @return self
   *   Self for chaining.
   */
  public function setRunner(Runner $runner): self {
    $this->hasRunnerTraitRunner = $runner;

    return $this;
  }


}
