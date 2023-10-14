<?php

namespace AssertType;

use AKlump\CheckPages\Assert;
use AKlump\CheckPages\AssertType\Contains;
use AKlump\CheckPages\AssertType\Equals;
use AKlump\CheckPages\AssertType\NotContains;
use AKlump\CheckPages\AssertType\NotEquals;
use AKlump\CheckPages\Parts\Test;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\AssertType\Contains
 * @covers \AKlump\CheckPages\AssertType\NotContains
 */
class EqualsNotEqualsTest extends TestCase {

  public function dataFortestInvokeProvider() {
    $tests = [];

    $tests[] = [
      [TRUE, FALSE],
      [1, 0],
      'apple',
      ['apple'],
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
  public function testEquals(array $expected_final_results, array $expected_counts, string $value, $haystack) {
    $assert = new Assert(1, [], $this->createMock(Test::class));
    $assert->setAssertion(Assert::ASSERT_EQUALS, $value);
    $countable = [];
    $result = (new Equals())($assert, $haystack, $countable);
    $this->assertCount($expected_counts[0], $countable);
    $this->assertSame($expected_final_results[0], $result);
  }

  /**
   * @dataProvider dataFortestInvokeProvider
   */
  public function testNotEquals(array $expected_final_results, array $expected_counts, string $value, $haystack) {
    $assert = new Assert(1, [], $this->createMock(Test::class));
    $assert->setAssertion(Assert::ASSERT_NOT_EQUALS, $value);
    $countable = [];
    $result = (new NotEquals())($assert, $haystack, $countable);
    $this->assertCount($expected_counts[1], $countable);
    $this->assertSame($expected_final_results[1], $result);
  }

  public function testEqualsWithContainsThrows() {
    $this->expectException(\InvalidArgumentException::class);
    $assert = new Assert(1, [], $this->createMock(Test::class));
    $assert->setAssertion(Assert::ASSERT_CONTAINS, 'foo');
    $countable = [];
    (new Equals())($assert, [], $countable);
  }

  public function testNotEqualsWithNotContainsThrows() {
    $this->expectException(\InvalidArgumentException::class);
    $assert = new Assert(1, [], $this->createMock(Test::class));
    $assert->setAssertion(Assert::ASSERT_NOT_CONTAINS, 'foo');
    $countable = [];
    (new NotEquals())($assert, [], $countable);
  }
}
