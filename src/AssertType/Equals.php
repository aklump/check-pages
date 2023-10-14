<?php

namespace AKlump\CheckPages\AssertType;

use AKlump\CheckPages\Assert;
use RuntimeException;

class Equals extends LogicBase {

  use EqualsTrait;

  protected function getExpectedAssertType(): string {
    return Assert::ASSERT_EQUALS;
  }

  public function __invoke(Assert $assert, $haystack, array &$countable): bool {
    parent::__invoke($assert, $haystack, $countable);

    $countable = [];
    $flattened_haystack = implode('", "', $haystack);
    foreach ($haystack as $item) {
      $passes_test = $this->equals($item, $this->value);
      if ($passes_test) {
        $assert->setNeedleIfNotSet($item);
        $countable[] = $item;
      }
      else {
        $reasons[] = $this->getReason($flattened_haystack, $this->value);
      }
      $this->setFinalResult($passes_test);
    }
    if (!empty($reasons)) {
      $assert->setReason(implode('. ', $reasons));
    }

    return $this->finalResult;
  }

  protected function getReason(...$sprintf_values): string {
    return sprintf("The actual value\n│\n│   \"%s\"\n│\n│   is not the expected\n│\n│   \"%s\"\n│", ...$sprintf_values);
  }

}
