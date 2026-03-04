<?php

namespace AKlump\CheckPages\Traits;


use AKlump\CheckPages\Parts\Test;

trait HasTestTrait {

  private $hasTestTraitTest;

  /**
   * @return NULL|\AKlump\CheckPages\Parts\Test
   */
  public function getTest(): ?Test {
    return $this->hasTestTraitTest;
  }

  /**
   * @param \AKlump\CheckPages\Parts\Test $test
   *
   * @return $this
   *   Self for chaining.
   */
  public function setTest(Test $test) {
    $this->hasTestTraitTest = $test;

    return $this;
  }


}
