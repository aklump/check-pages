<?php

namespace Helpers;

use AKlump\CheckPages\Helpers\GetResultCodePerExpectations;
use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\TAP\Result;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\Helpers\GetResultCodePerExpectations
 * @uses   \AKlump\CheckPages\Parts\Suite
 * @uses   \AKlump\CheckPages\Parts\Test
 * @uses   \AKlump\CheckPages\Traits\HasConfigTrait
 * @uses   \AKlump\CheckPages\Traits\HasRunnerTrait
 */
class GetResultCodePerExpectationsTest extends TestCase {

  public function testReturnsFailWhenTestPassedAndExpectedOutcomeIsFail() {
    $runner = $this->createMock(Runner::class);
    $suite = new Suite('', $runner);
    $test = new Test(1, [
      'expected outcome' => 'fail',
    ], $suite);
    $test->setPassed();
    $test->setResult(Result::OK);
    $result = (new GetResultCodePerExpectations())($test);
    $this->assertSame(Test::FAILED, $result);
  }

  public function testReturnsPassWhenTestFailedAndExpectedOutcomeIsFail() {
    $runner = $this->createMock(Runner::class);
    $suite = new Suite('', $runner);
    $test = new Test(1, [
      'expected outcome' => 'fail',
    ], $suite);
    $test->setFailed();
    $test->setResult(Result::NOT_OK);

    $result = (new GetResultCodePerExpectations())($test);
    $this->assertSame(Test::PASSED, $result);
  }
}
