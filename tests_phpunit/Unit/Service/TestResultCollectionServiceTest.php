<?php

namespace AKlump\CheckPages\Tests\Unit\Service;

use AKlump\CheckPages\Collections\TestResultCollection;
use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\Service\TestResultCollectionStorage;
use AKlump\CheckPages\Tests\TestingTraits\TestWithFilesTrait;
use AKlump\CheckPages\Tests\TestingTraits\TestWithTestCollectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\Service\TestResultCollectionStorage
 * @uses   \AKlump\CheckPages\Collections\TestResult
 * @uses   \AKlump\CheckPages\Collections\TestResultCollection
 */
class TestResultCollectionServiceTest extends TestCase {

  use TestWithFilesTrait;
  use TestWithTestCollectionTrait;

  public function testCantWriteFileThrows() {

    $filepath = $this->getTestFileFilepath('ipsum.csv', TRUE);
    chmod($filepath, 0555);

    $collection = new TestResultCollection();
    $this->addTestToCollection($collection, 'foo', Test::PASSED);

    $this->expectException(\RuntimeException::class);
    (new TestResultCollectionStorage())->save($filepath, $collection);
  }

  public function testSaveThenLoadWorksAsExpected() {
    $collection = new TestResultCollection();
    $this->addTestToCollection($collection, 'foo', Test::PASSED);

    $filepath = $this->getTestFileFilepath('lorem.csv');
    (new TestResultCollectionStorage())->save($filepath, $collection);
    $this->assertFileExists($filepath);

    $loaded = (new TestResultCollectionStorage())->load($filepath);
    $this->assertCount(1, $loaded);

    /** @var \AKlump\CheckPages\Collections\TestResult $test */
    $test = $loaded->first();
    $this->assertSame('foo', $test->getSuiteId());
    $this->assertSame(Test::PASSED, $test->getResult());
  }

  public function testLoadWhenFileDoesntExistReturnsNull() {
    $filepath = $this->getTestFileFilepath('foo.csv');
    $this->assertNull((new TestResultCollectionStorage())->load($filepath));
  }
}
