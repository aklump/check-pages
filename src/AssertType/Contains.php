<?php

namespace AKlump\CheckPages\AssertType;

use AKlump\CheckPages\Assert;

class Contains extends LogicBase {

  protected function getExpectedAssertType(): string {
    return Assert::ASSERT_CONTAINS;
  }

  public function __invoke(Assert $assert, $haystack, array &$countable): bool {
    parent::__invoke($assert, $haystack, $countable);
    $countable = [];
    foreach ($haystack as $item) {
      $passes_test = $this->applyCallbackWithVariations($item, function ($item_variation) use ($assert) {
        if (strpos(strval($item_variation), strval($this->value)) !== FALSE) {
          $assert->setNeedleIfNotSet($item_variation);

          return TRUE;
        }

        return FALSE;
      });
      if ($passes_test) {
        $countable[] = $item;
      }
      else {
        $reasons[] = $this->getReason($this->value);
      }
      $this->setFinalResult($passes_test);
    }
    if (!empty($reasons)) {
      $assert->setReason(implode('. ', $reasons));
    }

    return $this->finalResult;
  }

  protected function getReason(...$sprintf_values): string {
    return sprintf("Unable to find:\n\n> %s\n\n", ...$sprintf_values);
  }

}
