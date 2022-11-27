<?php

namespace AKlump\CheckPages\Traits;

/**
 * Use with objects that can have a pass/fail status.
 */
trait PassFailTrait {

  /**
   * If this is boolean, you can infer the test has been run.
   *
   * @var null|boolean
   */
  private $passFailTraitFailed = NULL;

  /**
   * @param bool $failed
   *
   * @return
   *   Self for chaining.
   */
  public function setFailed(): self {
    $this->passFailTraitFailed = TRUE;

    return $this;
  }

  /**
   * @param bool $failed
   *
   * @return
   *   Self for chaining.
   */
  public function setPassed(): self {
    $this->passFailTraitFailed = FALSE;

    return $this;
  }

  public function hasFailed(): bool {
    return $this->passFailTraitFailed === TRUE;
  }

  public function hasPassed(): bool {
    return $this->passFailTraitFailed === FALSE;
  }

}
