<?php

namespace AKlump\CheckPages\Tests\Unit;

use AKlump\CheckPages\Collections\TestResult;
use AKlump\CheckPages\Collections\TestResultCollection;

trait TestWithTestCollectionTrait {

  private function addTestToCollection(TestResultCollection $collection, string $suite_id, string $result) {
    static $current_test_id = 1;
    $collection->add((new TestResult())->setGroupId('foo')
      ->setSuiteId($suite_id)
      ->setTestId($current_test_id++)
      ->setResult($result));
  }
}
