<?php

namespace AKlump\CheckPages\Service;

use AKlump\CheckPages\Collections\TestResult;
use AKlump\CheckPages\Collections\TestResultCollection;

class TestResultCollectionStorage {

  public function load(string $filepath): ?TestResultCollection {
    if (!file_exists($filepath)) {
      return null;
    }
    $result_collection = new TestResultCollection();
    $fp = fopen($filepath, 'r');
    // Throw out the header row.
    fgetcsv($fp);
    while (($csv = fgetcsv($fp))) {
      list($group, $suite, $test, $result) = $csv;
      $result_collection->add((new TestResult())
        ->setGroupId($group)
        ->setSuiteId($suite)
        ->setTestId($test)
        ->setResult($result)
      );
    }
    fclose($fp);

    return $result_collection;
  }

  public function save(string $filepath, TestResultCollection $collection): void {
    try {
      $fp = fopen($filepath, 'w');
      if (FALSE === $fp) {
        throw new \RuntimeException();
      }
      fputcsv($fp, ['group', 'suite', 'test', 'result']);
      foreach ($collection as $test_result) {
        $fields = [
          $test_result->getGroupId(),
          $test_result->getSuiteId(),
          $test_result->getTestId(),
          $test_result->getResult(),
        ];
        $put = fputcsv($fp, $fields);
        if (FALSE === $put) {
          throw new \RuntimeException();
        }
      }
      fclose($fp);
    }
    catch (\Exception $exception) {
      throw new \RuntimeException(sprintf('Cannot save collection: %s', $filepath));
    }
  }

}
