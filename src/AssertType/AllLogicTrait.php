<?php

namespace AKlump\CheckPages\AssertType;

trait AllLogicTrait {

  abstract protected function getExpectedAssertType(): string;

  abstract protected function getReason(...$sprintf_values): string;

  /**
   * Apply a callback using variations of $item.
   *
   * @param $item
   *   This may contain special characters like &nbps;, which we want to match
   *   against a ' ' for loose-matching.  This value and variances based on
   *   string replacements will be passed to $callback.
   * @param callable $callback
   *   This should receive one argument and return a true based on comparing
   *   that item to $this->asserValue.  The callback will be called more than
   *   once, using variations of $item.  Only one pass is necessary for a true
   *   response.
   *
   * @return bool
   *   True if the callback returns true at least once.
   */
  protected function applyCallbackWithVariations($item, callable $callback): bool {
    $result = $callback($item);
    if (!$result && is_string($item)) {
      // Replace ASCII 160 with 32.
      $result = $callback(str_replace(' ', ' ', $item));
    }

    return $result;
  }

}
