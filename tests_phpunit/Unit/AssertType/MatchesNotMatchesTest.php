<?php

namespace AKlump\CheckPages\Tests\Unit\AssertType;

use AKlump\CheckPages\Assert;
use AKlump\CheckPages\AssertType\Matches;
use AKlump\CheckPages\AssertType\NotMatches;
use AKlump\CheckPages\Parts\Test;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\AssertType\Matches
 * @covers \AKlump\CheckPages\AssertType\NotMatches
 * @uses   \AKlump\CheckPages\Assert
 * @uses   \AKlump\CheckPages\AssertType\AllLogicTrait
 * @uses   \AKlump\CheckPages\AssertType\LogicBase
 * @uses   \AKlump\CheckPages\Traits\HasConfigTrait
 * @uses   \AKlump\CheckPages\AssertType\NotLogicBase
 */
class MatchesNotMatchesTest extends TestCase {

  public function dataFortestInvokeProvider() {
    $tests = [];

    $tests[] = [
      [TRUE, FALSE],
      [1, 0],
      '#/node\/(\d+?)\/edit#',
      ['/node/456/edit'],
      ['456', NULL],
    ];

    $tests[] = [
      [TRUE, FALSE],
      [1, 0],
      '/t-node--\d+/',
      ['fc-day-grid-event fc-h-event fc-event fc-start fc-end t-node--3250 is-not-published fc-draggable'],
      ['t-node--3250', NULL],
    ];


    $tests[] = [
      [TRUE, FALSE],
      [3, 1],
      '/^foo/',
      ['foodie', 'fool', 'idiot', 'footballer'],
      ['foo', 'idiot'],
    ];


    $tests[] = [
      [TRUE, FALSE],
      [1, 2],
      '/ipsum/',
      ['lorem', 'ipsum', 'dolar'],
      ['ipsum', 'lorem'],
    ];

    $tests[] = [
      [TRUE, FALSE],
      [1, 0],
      '/BLOG/i',
      ['My blog post'],
      ['blog', NULL],
    ];

    $tests[] = [
      [FALSE, TRUE],
      [0, 0],
      '',
      [],
      [NULL, NULL],
    ];

    $tests[] = [
      [TRUE, FALSE],
      [1, 2],
      '/foobar/i',
      ['lorem', 'FooBar', 'ipsum'],
      ['FooBar', 'lorem'],
    ];

    return $tests;
  }

  /**
   * @dataProvider dataFortestInvokeProvider
   */
  public function testMatches(array $expected_final_results, array $expected_counts, string $regex, $haystack, $needle) {
    $assert = new Assert(1, [], $this->createMock(Test::class));
    $assert->setAssertion(Assert::ASSERT_MATCHES, $regex);
    $countable = [];
    $result = (new Matches())($assert, $haystack, $countable);
    $this->assertCount($expected_counts[0], $countable);
    $this->assertSame($expected_final_results[0], $result);
    $this->assertSame($needle[0], $assert->getNeedle());
  }

  /**
   * @dataProvider dataFortestInvokeProvider
   */
  public function testNotMatches(array $expected_final_results, array $expected_counts, string $value, $haystack, $needle) {
    $assert = new Assert(1, [], $this->createMock(Test::class));
    $assert->setAssertion(Assert::ASSERT_NOT_MATCHES, $value);
    $countable = [];
    $result = (new NotMatches())($assert, $haystack, $countable);
    $this->assertCount($expected_counts[1], $countable);
    $this->assertSame($expected_final_results[1], $result);
    $this->assertSame($needle[1], $assert->getNeedle());
  }

  public function testMatchesWithContainsThrows() {
    $this->expectException(\InvalidArgumentException::class);
    $assert = new Assert(1, [], $this->createMock(Test::class));
    $assert->setAssertion(Assert::ASSERT_CONTAINS, 'foo');
    $countable = [];
    (new Matches())($assert, [], $countable);
  }

  public function testNotMatchesWithNotContainsThrows() {
    $this->expectException(\InvalidArgumentException::class);
    $assert = new Assert(1, [], $this->createMock(Test::class));
    $assert->setAssertion(Assert::ASSERT_NOT_CONTAINS, 'foo');
    $countable = [];
    (new NotMatches())($assert, [], $countable);
  }

}
