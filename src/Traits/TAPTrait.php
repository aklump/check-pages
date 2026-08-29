<?php

namespace AKlump\CheckPages\Traits;

use AKlump\CheckPages\TAP\Directive;
use AKlump\CheckPages\TAP\Result;

trait TAPTrait {

  private Result $TAPTraitResult;

  private string $TAPTraitReason;

  private Directive $TAPTraitDirective;

  public function getDirective(): Directive {
    return $this->TAPTraitDirective ?? Directive::NONE;
  }

  public function setDirective(Directive $directive, string $reason = ''): self {
    $this->TAPTraitDirective = $directive;
    $this->TAPTraitReason = $reason;

    return $this;
  }

  public function isSkipped(): bool {
    return match ($this->getDirective()) {
      Directive::SKIP => TRUE,
      default => FALSE,
    };
  }

  public function setResult(Result $result): self {
    $this->TAPTraitResult = $result;

    return $this;
  }

  public function getResult(): Result {
    return $this->TAPTraitResult ?? Result::NONE;
  }
}
