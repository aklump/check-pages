<?php

namespace AKlump\CheckPages\Traits;

use AKlump\CheckPages\Parts\Suite;

trait HasSuiteTrait {

  private $hasSuiteTraitSuite;

  /**
   * @return \AKlump\CheckPages\Parts\Suite
   */
  public function getSuite(): Suite {
    return $this->hasSuiteTraitSuite;
  }

  /**
   * @param \AKlump\CheckPages\Parts\Suite $suite
   *
   * @return self
   *   Self for chaining.
   */
  public function setSuite(Suite $suite): self {
    $this->hasSuiteTraitSuite = $suite;

    return $this;
  }

}
