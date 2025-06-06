<?php

namespace AKlump\CheckPages\AssertType;

use AKlump\CheckPages\Assert;

class NotText extends NotLogicBase {

  protected function getExpectedAssertType(): string {
    return Assert::ASSERT_NOT_TEXT;
  }

  /**
   * Get text-only content from $string.
   *
   * @param mixed $value
   *   Remove anything that should be ignored during a text comparison, e.g.
   *   HTML tags, leading/trailing whitespace, etc.
   *
   * @return mixed
   *   The prepared text.
   */
  private function stripNonTextChars($value) {
    if (is_string($value)) {
      $value = strip_tags($value);
      $value = trim($value);
    }

    return $value;
  }

  public function __invoke(Assert $assert, $haystack, array &$countable): bool {
    parent::__invoke($assert, $haystack, $countable);
    $this->value = $this->stripNonTextChars($this->value);
    $this->value = $this->getStringValueIfPossible($this->value);

    $countable = [];
    $flattened_haystack = implode('", "', $haystack);
    foreach ($haystack as $item) {
      $item = $this->stripNonTextChars($item);
      $passes_test = $this->applyCallbackWithVariations($item, function ($item_variation) use ($assert) {
        if (is_string($this->value) && strcmp($item_variation, $this->value) !== 0) {
          $assert->setNeedleIfNotSet($item_variation);

          return TRUE;
        }

        return FALSE;
      });
      if ($passes_test) {
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
    return sprintf("The text value\n│\n│   \"%s\"\n│\n│   is not expected.\n│", ...$sprintf_values);
  }

}
