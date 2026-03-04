<?php

namespace AKlump\CheckPages\Traits;

use AKlump\CheckPages\Parts\Suite;

trait HasSuiteTrait {

  private $hasSuiteTraitSuite;

  /**
   * @return NULL|\AKlump\CheckPages\Parts\Suite
   */
  public function getSuite(): ?Suite {
    return $this->hasSuiteTraitSuite;
  }

  /**
   * @param \AKlump\CheckPages\Parts\Suite $suite
   *
   * @return $this
   *   Self for chaining.
   */
  public function setSuite(Suite $suite) {
    $this->hasSuiteTraitSuite = $suite;

    return $this;
  }

}
