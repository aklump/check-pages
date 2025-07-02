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

    $reasons = [];
    $countable = [];
    foreach ($haystack as $item) {
      $passes_test = $this->applyCallbackWithVariations($item, function ($item_variation) use ($assert) {
        if (preg_match((string) $this->value, (string) $item_variation, $matches)) {
          $needle = array_pop($matches);
          $assert->setNeedleIfNotSet($needle);

          return TRUE;
        }

        return FALSE;
      });
      if ($passes_test) {
        $countable[] = $item;
      }
      else {
        $reasons[] = $this->getReason($item, $this->value);
        if (substr($this->value, 0, 1) !== substr($this->value, -1)) {
          $reasons[] = 'Your RegEx appears to be missing delimiter(s).';
        }
      }
      $this->setFinalResult($passes_test);
    }
    if (!empty($reasons)) {
      $reasons[] = NULL;
      $assert->setReason(implode(PHP_EOL, $reasons));
    }

    return $this->finalResult;
  }

  protected function getReason(...$sprintf_values): string {
    return sprintf('❗ %s', ...$sprintf_values);
  }

}
