<?php

namespace AKlump\CheckPages\Tests\Unit\Helpers;

use AKlump\CheckPages\Helpers\FilterSuites;
use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\SuiteCollection;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\Helpers\FilterSuites
 * @uses   \AKlump\CheckPages\Parts\Suite
 * @uses   \AKlump\CheckPages\SuiteCollection
 * @uses   \AKlump\CheckPages\Traits\HasRunnerTrait
 */
class FilterSuitesTest extends TestCase {

  public function testIfExactMatchBySuiteIDDoNotPatternMatch() {
    $runner = $this->createMock(Runner::class);
    $suites = new SuiteCollection();
    $suites->add((new Suite('day', $runner))->setGroup('api_day'));
    $suites->add((new Suite('day_updates', $runner))->setGroup('api_day'));
    $suites->add((new Suite('days', $runner))->setGroup('api_day'));

    foreach (['api_day/day', 'day', '#day$#', '#^api_day/day$#'] as $filter) {
      $result = (new FilterSuites())($suites, $filter);
      $this->assertSame('day', $result[0]->id());
      $this->assertCount(1, $result);
    }

    foreach (['api_day/days', 'days'] as $filter) {
      $result = (new FilterSuites())($suites, $filter);
      $this->assertSame('days', $result[0]->id());
      $this->assertCount(1, $result);
    }

    foreach (['api_day/da', 'da'] as $filter) {
      $result = (new FilterSuites())($suites, $filter);
      $this->assertSame('day', $result[0]->id());
      $this->assertSame('day_updates', $result[1]->id());
      $this->assertSame('days', $result[2]->id());
      $this->assertCount(3, $result);
    }
  }

  public function dataFortestFilterByIdWorksAsExpectedProvider() {
    $tests = [];
    $tests[] = [1, '/foo'];
    $tests[] = [3, 'foo/'];
    $tests[] = [3, 'fo'];

    // An exact match by suite ID will only return that suite.
    $tests[] = [1, 'foo'];

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
