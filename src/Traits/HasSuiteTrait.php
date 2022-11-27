<?php

namespace AKlump\CheckPages\Traits;

use AKlump\CheckPages\Parts\Suite;

trait HasSuiteTrait {

  private $hasSuiteTraitSuite;

  /**
   * @return mixed
   */
  public function getSuite(): Suite {
    return $this->hasSuiteTraitSuite;
  }

  /**
   * @param mixed $suite
   *
   * @return
   *   Self for chaining.
   */
  public function setSuite(Suite $suite): self {
    $this->hasSuiteTraitSuite = $suite;

    return $this;
  }

}
