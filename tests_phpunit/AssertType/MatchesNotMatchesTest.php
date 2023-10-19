<?php

namespace AssertType;

use AKlump\CheckPages\Assert;
use AKlump\CheckPages\AssertType\Matches;
use AKlump\CheckPages\AssertType\NotMatches;
use AKlump\CheckPages\Parts\Test;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\AssertType\Matches
 * @covers \AKlump\CheckPages\AssertType\NotMatches
 */
class MatchesNotMatchesTest extends TestCase {

  public function dataFortestInvokeProvider() {
    $tests = [];

    $tests[] = [
      [TRUE, FALSE],
      [1, 0],
      '/t-node--\d+/',
      ['fc-day-grid-event fc-h-event fc-event fc-start fc-end t-node--3250 is-not-published fc-draggable'],
    ];

    $tests[] = [
      [TRUE, FALSE],
      [3, 1],
      '/^foo/',
      ['foodie', 'fool', 'idiot', 'footballer'],
    ];

    $tests[] = [
      [TRUE, FALSE],
      [1, 2],
      '/ipsum/',
      ['lorem', 'ipsum', 'dolar'],
    ];

    $tests[] = [
      [TRUE, FALSE],
      [1, 0],
      '/BLOG/i',
      ['My blog post'],
    ];

    $tests[] = [
      [FALSE, TRUE],
      [0, 0],
      '',
      [],
    ];

    return $tests;
  }

  /**
   * @dataProvider dataFortestInvokeProvider
   */
  public function testMatches(array $expected_final_results, array $expected_counts, string $value, $haystack) {
    $assert = new Assert(1, [], $this->createMock(Test::class));
    $assert->setAssertion(Assert::ASSERT_MATCHES, $value);
    $countable = [];
    $result = (new Matches())($assert, $haystack, $countable);
    $this->assertCount($expected_counts[0], $countable);
    $this->assertSame($expected_final_results[0], $result);
  }

  /**
   * @dataProvider dataFortestInvokeProvider
   */
  public function testNotMatches(array $expected_final_results, array $expected_counts, string $value, $haystack) {
    $assert = new Assert(1, [], $this->createMock(Test::class));
    $assert->setAssertion(Assert::ASSERT_NOT_MATCHES, $value);
    $countable = [];
    $result = (new NotMatches())($assert, $haystack, $countable);
    $this->assertCount($expected_counts[1], $countable);
    $this->assertSame($expected_final_results[1], $result);
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
