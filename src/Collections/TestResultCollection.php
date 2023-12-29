<?php

namespace AKlump\CheckPages\Collections;

use AKlump\CheckPages\Parts\Test;
use Ramsey\Collection\AbstractCollection;

class TestResultCollection extends AbstractCollection {

  /** @var \AKlump\CheckPages\Collections\TestResult[] */
  protected array $data = [];

  /**
   * Generates an ID string based on the properties of the given TestResult object.
   *
   * @param TestResult $test_result
   *   The TestResult object from which to generate the ID.
   *
   * @return string
   *   The generated ID string that will be used as the collection index.s
   */
  private static function getIdByTestResult(TestResult $test_result): string {
    return md5(implode('.', [
      $test_result->getGroupId(),
      $test_result->getSuiteId(),
      $test_result->getTestId(),
    ]));
  }

  /**
   * @inheritDoc
   */
  public function getType(): string {
    return TestResult::class;
  }

  public function map(callable $callback): TestResultCollection {
    return new TestResultCollection(array_map($callback, $this->data));
  }

  /**
   * {@inheritdoc}
   */
  public function add($element): bool {
    $key = self::getIdByTestResult($element);
    if ($this->contains($element) && $element == $this->data[$key]) {
      return FALSE;
    }
    $this->data[$key] = $element;

    return TRUE;
  }

  /**
   * Looks for an item having all the same properties ignoring the result value.
   *
   * @param $element
   * @param bool $strict
   *
   * @return bool
   *   True if the collection contains an item matching $element, however not
   *   checking the result value, which is ignored.
   */
  public function contains($element, bool $strict = TRUE): bool {
    $key = self::getIdByTestResult($element);

    return array_key_exists($key, $this->data);
  }

  /**
   * {@inheritdoc}
   */
  public function filter(callable $callback): TestResultCollection {
    $collection = clone $this;
    $collection->data = array_merge([], array_filter($collection->data, $callback));

    return $collection;
  }


  public function filterCompletedSuites(): TestResultCollection {
    $not_tests = $this->filter(fn(TestResult $item) => $item->getResult() === Test::PENDING)
      ->toArray();
    if (empty($not_tests)) {
      return $this;
    }
    $not_suites = array_unique(array_map(fn(TestResult $test_result) => $test_result->getSuiteId(), $not_tests));

    return $this->filter(function (TestResult $result) use ($not_suites) {
      return !in_array($result->getSuiteId(), $not_suites);
    });
  }

  public function filterPassedSuites(): TestResultCollection {
    $not_tests = $this->filter(fn(TestResult $item) => $item->getResult() !== Test::PASSED)
      ->toArray();
    if (empty($not_tests)) {
      return $this;
    }
    $not_suites = array_unique(array_map(fn(TestResult $test_result) => $test_result->getSuiteId(), $not_tests));

    return $this->filter(function (TestResult $result) use ($not_suites) {
      return !in_array($result->getSuiteId(), $not_suites);
    });
  }

}
