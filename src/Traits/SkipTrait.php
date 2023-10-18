<?php

namespace AKlump\CheckPages\Traits;


trait SkipTrait {

  private $isSkipped = FALSE;

  public function isSkipped(): bool {
    return $this->isSkipped;
  }

  public function setIsSkipped(bool $isSkipped): self {
    $this->isSkipped = $isSkipped;

    return $this;
  }

}
