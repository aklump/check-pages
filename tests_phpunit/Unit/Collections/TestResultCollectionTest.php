<?php

namespace AKlump\CheckPages\Tests\Unit\Collections;

use AKlump\CheckPages\Collections\TestResult;
use AKlump\CheckPages\Collections\TestResultCollection;
use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\Tests\TestingTraits\TestWithTestCollectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\Collections\TestResultCollection
 * @uses   \AKlump\CheckPages\Collections\TestResult
 */
class TestResultCollectionTest extends TestCase {

  use TestWithTestCollectionTrait;

  public function testMap() {
    $collection = new TestResultCollection();
    $this->addTestToCollection($collection, 'foo', Test::PENDING);
    $collection->map(fn(TestResult $result) => $result->setResult(Test::PASSED));
    $this->assertSame(Test::PASSED, $collection->first()->getResult());
  }

  public function testGetType() {
    $this->assertSame(TestResult::class, (new TestResultCollection())->getType());
  }

  public function testAddReturnValueIsExpectedBoolean() {
    $collection = new TestResultCollection();
    $result = new TestResult();
    $result->setGroupId('foo')->setSuiteId('bar')->setTestId('baz');
    $result->setResult(Test::PENDING);
    $this->assertTrue($collection->add($result));
    $result->setResult(Test::PASSED);
    $this->assertFalse($collection->add($result));
  }

  public function testWithoutCompletedTests() {
    $collection = new TestResultCollection();
    $this->addTestToCollection($collection, 'foo', Test::PASSED);
    $this->addTestToCollection($collection, 'foo', Test::FAILED);
    $this->addTestToCollection($collection, 'foo', Test::SKIPPED);
    $this->addTestToCollection($collection, 'bar', Test::PASSED);
    $this->addTestToCollection($collection, 'bar', Test::FAILED);
    $this->addTestToCollection($collection, 'bar', Test::SKIPPED);
    $this->addTestToCollection($collection, 'bar', Test::PENDING);
    $completed_suite_tests = $collection->withoutCompletedTests();
    $this->assertCount(1, $completed_suite_tests);
  }

  public function testWithoutCompletedTestsAlt() {
    $collection = new TestResultCollection();
    $this->addTestToCollection($collection, 'foo', Test::PASSED);
    $this->addTestToCollection($collection, 'foo', Test::PASSED);
    $result = $collection->withoutCompletedTests();
    $this->assertCount(0, $result);
  }


  public function testWithoutPassedTests() {
    $collection = new TestResultCollection();
    $this->addTestToCollection($collection, 'foo', Test::PASSED);
    $this->addTestToCollection($collection, 'foo', Test::FAILED);
    $this->addTestToCollection($collection, 'foo', Test::SKIPPED);
    $this->addTestToCollection($collection, 'bar', Test::PASSED);
    $this->addTestToCollection($collection, 'bar', Test::FAILED);
    $this->addTestToCollection($collection, 'bar', Test::SKIPPED);
    $this->addTestToCollection($collection, 'bar', Test::PENDING);
    $results = $collection->withoutPassedTests();
    $this->assertCount(5, $results);
  }

  public function testWithoutPassedTestsAlt() {
    $collection = new TestResultCollection();
    $this->addTestToCollection($collection, 'foo', Test::PASSED);
    $this->addTestToCollection($collection, 'foo', Test::PASSED);
    $result = $collection->withoutPassedTests();
    $this->assertCount(0, $result);
  }


}
