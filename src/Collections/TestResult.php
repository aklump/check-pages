<?php

namespace AKlump\CheckPages\Collections;

use AKlump\CheckPages\Parts\Test;

class TestResult {

  private string $groupId;

  private string $suiteId;

  private string $result;

  private string $testId;

  public function getTestId(): string {
    return $this->testId;
  }

  public function setTestId(string $testId): self {
    $this->testId = $testId;

    return $this;
  }

  public function getGroupId(): string {
    return $this->groupId;
  }

  public function setGroupId(string $groupId): self {
    $this->groupId = $groupId;

    return $this;
  }

  public function getSuiteId(): string {
    return $this->suiteId;
  }

  public function setSuiteId(string $suiteId): self {
    $this->suiteId = $suiteId;

    return $this;
  }

  public function getResult(): string {
    return $this->result;
  }

  /**
   * @param string $result
   *
   * @return $this
   *
   * @see \AKlump\CheckPages\Parts\Test::PENDING
   * @see \AKlump\CheckPages\Parts\Test::SKIPPED
   * @see \AKlump\CheckPages\Parts\Test::PASSED
   * @see \AKlump\CheckPages\Parts\Test::FAILED
   */
  public function setResult(string $result): self {
    $result = strtoupper($result);
    $this->tryValidateResultValue($result);
    $this->result = $result;

    return $this;
  }

  private function tryValidateResultValue(string $value) {
    if (!in_array($value, [
      Test::PENDING,
      Test::SKIPPED,
      Test::PASSED,
      Test::FAILED,
    ])) {
      throw new \InvalidArgumentException(sprintf('Invalid result value: %s', $value));
    }
  }

}
