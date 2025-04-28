<?php

namespace Helpers;

use AKlump\CheckPages\Helpers\GetResultCodePerExpectations;
use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\Parts\Test;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\Helpers\GetResultCodePerExpectations
 */
class GetResultCodePerExpectationsTest extends TestCase {

  public function testReturnsFailWhenTestPassedAndExpectedOutcomeIsFail() {
    $runner = $this->createMock(Runner::class);
    $suite = new Suite('', $runner);
    $test = new Test('', [
      'expected outcome' => 'fail',
    ], $suite);
    $test->setPassed();
    $result = (new GetResultCodePerExpectations())($test);
    $this->assertSame(Test::FAILED, $result);
  }

  public function testReturnsPassWhenTestFailedAndExpectedOutcomeIsFail() {
    $runner = $this->createMock(Runner::class);
    $suite = new Suite('', $runner);
    $test = new Test('', [
      'expected outcome' => 'fail',
    ], $suite);
    $test->setFailed();
    $result = (new GetResultCodePerExpectations())($test);
    $this->assertSame(Test::PASSED, $result);
  }
}
