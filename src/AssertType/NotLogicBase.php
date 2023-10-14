<?php

namespace AKlump\CheckPages\AssertType;

use AKlump\CheckPages\Assert;
use InvalidArgumentException;

abstract class NotLogicBase {

  use AllLogicTrait;

  protected $value;

  protected $finalResult;

  protected function setFinalResult(?bool $result): void {
    $this->finalResult = $this->finalResult ?? $result;
    if (FALSE !== $this->finalResult) {
      $this->finalResult = $result;
    }
  }

  public function __invoke(Assert $assert, array $haystack, array &$countable): bool {
    list($assert_type, $this->value) = $assert->getAssertion();
    if ($assert_type !== $this->getExpectedAssertType()) {
      throw new InvalidArgumentException(sprintf('Invalid assert type received: %s; expecting %s', $assert_type, $this->getExpectedAssertType()));
    }
    $initial_result = empty($haystack) ? TRUE : NULL;
    $this->setFinalResult($initial_result);

    return FALSE;
  }
}
