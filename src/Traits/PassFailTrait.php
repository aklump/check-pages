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
   * Clear any pass/fail result previously set.
   *
   * @return $this
   */
  public function clearResult() {
    $this->passFailTraitFailed = NULL;

    return $this;
  }

  /**
   * @param bool $failed
   *
   * @return $this
   */
  public function setFailed() {
    $this->passFailTraitFailed = TRUE;

    return $this;
  }

  /**
   * @param bool $failed
   *
   * @return $this
   */
  public function setPassed() {
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
