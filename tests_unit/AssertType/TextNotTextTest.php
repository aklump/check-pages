<?php

namespace AKlump\CheckPages\Tests\Unit\AssertType;

use AKlump\CheckPages\Assert;
use AKlump\CheckPages\AssertType\Contains;
use AKlump\CheckPages\AssertType\NotContains;
use AKlump\CheckPages\AssertType\NotText;
use AKlump\CheckPages\AssertType\Text;
use AKlump\CheckPages\Parts\Test;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\AssertType\Text
 * @covers \AKlump\CheckPages\AssertType\NotText
 * @uses   \AKlump\CheckPages\Assert
 * @uses   \AKlump\CheckPages\AssertType\NotLogicBase
 * @uses   \AKlump\CheckPages\Traits\HasConfigTrait
 * @uses   \AKlump\CheckPages\AssertType\LogicBase
 */
class TextNotTextTest extends TestCase {

  public function dataFortestInvokeProvider() {
    $tests = [];

    $tests[] = [
      [TRUE, FALSE],
      [1, 0],
      'foo',
      ['<h1>foo</h1>'],
    ];

    $tests[] = [
      [TRUE, FALSE],
      [2, 1],
      'foo',
      ['<h1>foo</h1>', '<h1>bar</h1>', '<h1>foo</h1>'],
    ];

    $tests[] = [
      [TRUE, FALSE],
      [2, 1],
      'foo',
      ['<h1>foo</h1>', '<h1>bar</h1>', '    foo  '],
    ];

    $tests[] = [
      [TRUE, FALSE],
      [2, 1],
      '  foo  ',
      ['<h1>foo</h1>', '<h1>bar</h1>', '    foo  '],
    ];

    $tests[] = [
      [TRUE, FALSE],
      [2, 1],
      'foo',
      ['<h1>foo</h1>', '<h1>bar</h1>', "\n\tfoo\t\t\n"],
    ];

    return $tests;
  }

  /**
   * @dataProvider dataFortestInvokeProvider
   */
  public function testNotText(array $expected_final_results, array $expected_counts, string $value, $haystack) {
    $assert = new Assert(1, [], $this->createMock(Test::class));
    $assert->setAssertion(Assert::ASSERT_NOT_TEXT, $value);
    $countable = [];
    $result = (new NotText())($assert, $haystack, $countable);
    $this->assertCount($expected_counts[1], $countable);
    $this->assertSame($expected_final_results[1], $result);
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
