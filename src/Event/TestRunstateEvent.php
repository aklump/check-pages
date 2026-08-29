<?php

namespace AKlump\CheckPages\Event;

use AKlump\CheckPages\TestRunState;

final class TestRunstateEvent {

  protected TestRunState $testRunState;

  protected TestRunState $previous;

  public function __construct(TestRunState $testRunState, ?TestRunState $previous = NULL) {
    $this->testRunState = $testRunState;
    $this->previous = $previous;
  }

  public function getTestRunState(): TestRunState {
    return $this->testRunState;
  }

  public function diff(): array {
    return $this->recursiveDiff(
      $this->testRunState->jsonSerialize(),
      $this->previous?->jsonSerialize()
    );
  }

  private function recursiveDiff(array $after, array $before): array {
    $diff = [];

    foreach ($after as $key => $afterValue) {
      if (!array_key_exists($key, $before)) {
        $diff[$key] = $afterValue;
        continue;
      }

      $beforeValue = $before[$key];

      if (is_array($afterValue) && is_array($beforeValue)) {
        $nestedDiff = $this->recursiveDiff($afterValue, $beforeValue);
        if ($nestedDiff !== []) {
          $diff[$key] = $nestedDiff;
        }
        continue;
      }

      if ($afterValue !== $beforeValue) {
        $diff[$key] = $afterValue;
      }
    }

    return $diff;
  }
}
