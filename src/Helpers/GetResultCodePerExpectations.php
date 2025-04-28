<?php

namespace AKlump\CheckPages\Helpers;

use AKlump\CheckPages\Parts\Test;

/**
 * Get the test result based on the expected outcome.
 *
 * @see Test::PASSED
 * @see Test::FAILED
 */
class GetResultCodePerExpectations {

  public function __invoke(Test $test): string {
    $result_code = $test->hasPassed() ? Test::PASSED : NULL;
    if (!$result_code) {
      $result_code = $test->hasFailed() ? Test::FAILED : NULL;
    }
    if (!$result_code) {
      $result_code = $test->isSkipped() ? Test::SKIPPED : NULL;
    }
    $result_code = $result_code ?? Test::PENDING;

    if (!$test->has('expected outcome') || in_array($result_code, [
        Test::SKIPPED,
        Test::PENDING,
      ])) {
      return $result_code;
    }
    $expected_outcome = $test->get('expected outcome');
    if (in_array($expected_outcome, [
      'fail',
      'failure',
    ])) {
      $result_code = $result_code === Test::PASSED ? Test::FAILED : Test::PASSED;
    }

    return $result_code;
  }
}


