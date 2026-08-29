<?php

namespace AKlump\CheckPages\Tests\TestingTraits;

use AKlump\CheckPages\Collections\TestResult;
use AKlump\CheckPages\Collections\TestResultCollection;

trait TestWithTestCollectionTrait {

  private function addTestToCollection(TestResultCollection $collection, string $suiteId, string $result, int $testNumber = NULL) {
    static $autoTestNumber = 1;
    $testNumber = $testNumber ?? $autoTestNumber++;
    $collection->add((new TestResult())->setGroupId('foo')
      ->setSuiteId($suiteId)
      ->setTestId($testNumber)
      ->setResult($result));
  }
}
