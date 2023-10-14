<?php

namespace AKlump\CheckPages\AssertType;

use AKlump\CheckPages\Assert;
use RuntimeException;

class Matches extends LogicBase {

  protected function getExpectedAssertType(): string {
    return Assert::ASSERT_MATCHES;
  }

  public function __invoke(Assert $assert, $haystack, array &$countable): bool {
    parent::__invoke($assert, $haystack, $countable);

    $countable = [];
    foreach ($haystack as $item) {
      $passes_test = $this->applyCallbackWithVariations($item, function ($item_variation) use ($assert) {
        if (preg_match($this->value, $item_variation, $matches)) {
          $assert->setNeedleIfNotSet($matches[0]);

          return TRUE;
        }

        return FALSE;
      });
      if ($passes_test) {
        $countable[] = $item;
      }
      else {
        $reasons[] = $this->getReason($item, $this->value);
      }
      $this->setFinalResult($passes_test);
    }
    if (!empty($reasons)) {
      $assert->setReason(implode('. ', $reasons));
    }

    return $this->finalResult;
  }

  protected function getReason(...$sprintf_values): string {
    return sprintf('Unable to match actual value \"%s\" using \"%s\"', ...$sprintf_values);
  }

}
