<?php

namespace AKlump\CheckPages\Tests\Unit\Collections;

use AKlump\CheckPages\Collections\TestResult;
use AKlump\CheckPages\Parts\Test;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\Collections\TestResult
 */
class TestResultTest extends TestCase {

  public function testGettersAndSetters() {
    $test_result = new TestResult();
    $test_result = $test_result->setGroupId('foo')
      ->setSuiteId('bar')
      ->setTestId('baz')
      ->setResult(Test::PASSED);
    $this->assertSame('foo', $test_result->getGroupId());
    $this->assertSame('bar', $test_result->getSuiteId());
    $this->assertSame('baz', $test_result->getTestId());
    $this->assertSame(Test::PASSED, $test_result->getResult());
  }

  public function testThrowsOnInvalidResultValue() {
    $this->expectException(\InvalidArgumentException::class);
    (new TestResult())->setResult('foo');
  }
}
