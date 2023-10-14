<?php

namespace AssertType;

use AKlump\CheckPages\Assert;
use AKlump\CheckPages\AssertType\Contains;
use AKlump\CheckPages\AssertType\NotContains;
use AKlump\CheckPages\AssertType\Text;
use AKlump\CheckPages\Parts\Test;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\AssertType\Contains
 * @covers \AKlump\CheckPages\AssertType\NotContains
 */
class TextTest extends TestCase {

  public function dataFortestInvokeProvider() {
    $tests = [];

    $tests[] = [
      [TRUE],
      [1],
      'foo',
      ['<h1>foo</h1>'],
    ];

    $tests[] = [
      [TRUE],
      [2],
      'foo',
      ['<h1>foo</h1>', '<h1>bar</h1>', '<h1>foo</h1>'],
    ];

    $tests[] = [
      [TRUE],
      [2],
      'foo',
      ['<h1>foo</h1>', '<h1>bar</h1>', '    foo  '],
    ];

    $tests[] = [
      [TRUE],
      [2],
      '  foo  ',
      ['<h1>foo</h1>', '<h1>bar</h1>', '    foo  '],
    ];

    $tests[] = [
      [TRUE],
      [2],
      'foo',
      ['<h1>foo</h1>', '<h1>bar</h1>', "\n\tfoo\t\t\n"],
    ];

    return $tests;
  }

  /**
   * @dataProvider dataFortestInvokeProvider
   */
  public function testText(array $expected_final_results, array $expected_counts, string $value, $haystack) {
    $assert = new Assert(1, [], $this->createMock(Test::class));
    $assert->setAssertion(Assert::ASSERT_TEXT, $value);
    $countable = [];
    $result = (new Text())($assert, $haystack, $countable);
    $this->assertCount($expected_counts[0], $countable);
    $this->assertSame($expected_final_results[0], $result);
  }

  public function testTextWithContainsThrows() {
    $this->expectException(\InvalidArgumentException::class);
    $assert = new Assert(1, [], $this->createMock(Test::class));
    $assert->setAssertion(Assert::ASSERT_CONTAINS, 'foo');
    $countable = [];
    (new Text())($assert, [], $countable);
  }

}
