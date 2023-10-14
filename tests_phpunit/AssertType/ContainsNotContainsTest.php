<?php

namespace AssertType;

use AKlump\CheckPages\Assert;
use AKlump\CheckPages\AssertType\Contains;
use AKlump\CheckPages\AssertType\NotContains;
use AKlump\CheckPages\Parts\Test;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\AssertType\Contains
 * @covers \AKlump\CheckPages\AssertType\NotContains
 */
class ContainsNotContainsTest extends TestCase {

  public function dataFortestInvokeProvider() {
    $tests = [];

    $tests[] = [
      [TRUE, FALSE],
      [3, 1],
      'foo',
      ['foodie', 'fool', 'idiot', 'footballer'],
    ];

    $tests[] = [
      [TRUE, FALSE],
      [1, 2],
      'ipsum',
      ['lorem', 'ipsum', 'dolar'],
    ];

    $tests[] = [
      [FALSE, TRUE],
      [0, 1],
      'BLOG',
      ['My blog post'],
    ];

    $tests[] = [
      [TRUE, FALSE],
      [1, 0],
      'blog',
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
    $assert->setAssertion(Assert::ASSERT_CONTAINS, $value);
    $countable = [];
    $result = (new Contains())($assert, $haystack, $countable);
    $this->assertCount($expected_counts[0], $countable);
    $this->assertSame($expected_final_results[0], $result);
  }

  /**
   * @dataProvider dataFortestInvokeProvider
   */
  public function testNotMatches(array $expected_final_results, array $expected_counts, string $value, $haystack) {
    $assert = new Assert(1, [], $this->createMock(Test::class));
    $assert->setAssertion(Assert::ASSERT_NOT_CONTAINS, $value);
    $countable = [];
    $result = (new NotContains())($assert, $haystack, $countable);
    $this->assertCount($expected_counts[1], $countable);
    $this->assertSame($expected_final_results[1], $result);
  }
  public function testContainsWithMatchesThrows() {
    $this->expectException(\InvalidArgumentException::class);
    $assert = new Assert(1, [], $this->createMock(Test::class));
    $assert->setAssertion(Assert::ASSERT_MATCHES, 'foo');
    $countable = [];
    (new Contains())($assert, [], $countable);
  }

  public function testNotContainsWithNotMatchesThrows() {
    $this->expectException(\InvalidArgumentException::class);
    $assert = new Assert(1, [], $this->createMock(Test::class));
    $assert->setAssertion(Assert::ASSERT_NOT_MATCHES, 'foo');
    $countable = [];
    (new NotContains())($assert, [], $countable);
  }
}
