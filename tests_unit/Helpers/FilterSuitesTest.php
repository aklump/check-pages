<?php

namespace AKlump\CheckPages\Tests\Unit\Helpers;

use AKlump\CheckPages\Helpers\FilterSuites;
use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\SuiteCollection;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\Helpers\FilterSuites
 */
class FilterSuitesTest extends TestCase {

  public function dataFortestFilterByIdWorksAsExpectedProvider() {
    $tests = [];
    $tests[] = [1, '/foo'];
    $tests[] = [3, 'foo/'];
    $tests[] = [3, 'foo'];
    $tests[] = [1, 'foo/lorem'];
    $tests[] = [1, '/l?rem/'];
    $tests[] = [1, 'lorem'];
    $tests[] = [1, 'ipsum'];
    $tests[] = [1, 'dolar'];
    $tests[] = [1, 'lor'];
    $tests[] = [1, 'sum'];

    return $tests;
  }

  /**
   * @dataProvider dataFortestFilterByIdWorksAsExpectedProvider
   */
  public function testFilterWorksWithIdsAsExpected(int $expected_count, string $filter) {
    $runner = $this->createMock(Runner::class);
    $suites = new SuiteCollection();
    $suites->add((new Suite('lorem', $runner))->setGroup('foo'));
    $suites->add((new Suite('ipsum', $runner))->setGroup('foo'));
    $suites->add((new Suite('dolar', $runner))->setGroup('bar'));
    $suites->add((new Suite('foo', $runner))->setGroup('foo'));
    $result = (new FilterSuites())($suites, $filter);
    $this->assertCount($expected_count, $result);
  }

  public function testResultNotSameInstance() {
    $suites = new SuiteCollection();
    $result = (new FilterSuites())($suites, '');
    $this->assertNotSame($result, $suites);
  }

}
