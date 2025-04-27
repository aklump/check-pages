<?php

namespace AKlump\CheckPages\Tests\TestingTraits;

use AKlump\CheckPages\Collections\TestResult;
use AKlump\CheckPages\Collections\TestResultCollection;

trait TestWithTestCollectionTrait {

  private function addTestToCollection(TestResultCollection $collection, string $suite_id, string $result, string $test_id = NULL) {
    static $current_test_id = 1;
    $test_id = $test_id ?? $current_test_id++;
    $collection->add((new TestResult())->setGroupId('foo')
      ->setSuiteId($suite_id)
      ->setTestId($test_id)
      ->setResult($result));
  }
}
