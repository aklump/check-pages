<?php

namespace AKlump\CheckPages\Tests\Unit\Service;

use AKlump\CheckPages\Collections\TestResultCollection;
use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\Service\Retest;
use AKlump\CheckPages\Tests\TestingTraits\TestWithTestCollectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\Service\Retest
 * @uses   \AKlump\CheckPages\Collections\TestResult
 * @uses   \AKlump\CheckPages\Collections\TestResultCollection
 */
class RetestTest extends TestCase {

  use TestWithTestCollectionTrait;

  public function testGetSuitesToIgnoreContinueWithPartiallyCompletedSuite() {
    $collection = new TestResultCollection();
    $this->addTestToCollection($collection, 'homepage', Test::PASSED, 1);
    $this->addTestToCollection($collection, 'homepage', Test::PASSED, 2);
    // We have to "continue" the full suites, because there is a pending test in
    // this suite.
    $this->addTestToCollection($collection, 'contact', Test::PASSED, 1);
    $this->addTestToCollection($collection, 'contact', Test::PENDING, 2);
    $this->addTestToCollection($collection, 'email', Test::PENDING, 1);
    $ignore_list = (new Retest())->getSuitesToIgnore($collection, Retest::MODE_CONTINUE);
    $this->assertCount(1, $ignore_list);
    $this->assertSame('foo/homepage', $ignore_list[0]);
  }

  public function testGetSuitesToIgnoreContinueAfterFullyCompletedSuite() {
    $collection = new TestResultCollection();
    $this->addTestToCollection($collection, 'homepage', Test::PASSED, 1);
    $this->addTestToCollection($collection, 'homepage', Test::PASSED, 2);
    $this->addTestToCollection($collection, 'contact', Test::PASSED, 1);
    $this->addTestToCollection($collection, 'contact', Test::FAILED, 2);
    $this->addTestToCollection($collection, 'email', Test::PENDING, 1);
    $ignore_list = (new Retest())->getSuitesToIgnore($collection, Retest::MODE_CONTINUE);
    $this->assertCount(2, $ignore_list);
    $this->assertSame('foo/homepage', $ignore_list[0]);
    $this->assertSame('foo/contact', $ignore_list[1]);
  }

  public function testGetSuitesToIgnoreRetestOneFailedOnePending() {
    $collection = new TestResultCollection();
    $this->addTestToCollection($collection, 'homepage', Test::PASSED);
    $this->addTestToCollection($collection, 'contact', Test::FAILED);
    $this->addTestToCollection($collection, 'email', Test::PENDING);
    $ignore_list = (new Retest())->getSuitesToIgnore($collection, Retest::MODE_RETEST);
    $this->assertCount(1, $ignore_list);
    $this->assertSame('foo/homepage', $ignore_list[0]);
  }

  public function testGetSuitesToIgnoreRetestOneFailed() {
    $collection = new TestResultCollection();
    $this->addTestToCollection($collection, 'homepage', Test::PASSED);
    $this->addTestToCollection($collection, 'contact', Test::FAILED);
    $this->addTestToCollection($collection, 'email', Test::PASSED);
    $ignore_list = (new Retest())->getSuitesToIgnore($collection, Retest::MODE_RETEST);
    $this->assertCount(2, $ignore_list);
    $this->assertSame('foo/homepage', $ignore_list[0]);
    $this->assertSame('foo/email', $ignore_list[1]);
  }

  public function testGetSuitesToIgnoreRetestAllPassed() {
    $collection = new TestResultCollection();
    $this->addTestToCollection($collection, 'homepage', Test::PASSED);
    $this->addTestToCollection($collection, 'contact', Test::PASSED);
    $this->addTestToCollection($collection, 'email', Test::PASSED);
    $ignore_list = (new Retest())->getSuitesToIgnore($collection, Retest::MODE_RETEST);
    $this->assertCount(3, $ignore_list);
  }

  public function testGetSuitesToIgnoreNoOptionsReturnsEmptyArray() {
    $collection = new TestResultCollection();
    $this->addTestToCollection($collection, 'homepage', Test::PASSED);
    $this->addTestToCollection($collection, 'contact', Test::PASSED);
    $this->addTestToCollection($collection, 'email', Test::PASSED);
    $ignore_list = (new Retest())->getSuitesToIgnore($collection, 0);
    $this->assertSame([], $ignore_list);
  }
}
