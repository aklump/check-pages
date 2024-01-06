<?php

namespace AKlump\CheckPages\Tests\Unit\Parts;

use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\Parts\TestRunner;
use AKlump\CheckPages\Tests\Unit\TestWithDispatcherTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\Parts\TestRunner
 */
class TestRunnerTest extends TestCase {

  use TestWithDispatcherTrait;

  public function testWhyGetsInterpolated() {
    $runner = $this->getRunner();
    $suite = new Suite('test', $runner);
    $suite->addTestByConfig([
      'set' => 'title',
      'value' => 'lorem',
    ]);
    $suite->addTestByConfig([
      'why' => 'Test for ${title}',
    ]);
    foreach ($suite->getTests() as $test) {
      (new TestRunner($test))->start();
    }
    $this->assertNotEmpty($test);
    $this->assertSame('Test for lorem', $test->get('why'));
  }

}
