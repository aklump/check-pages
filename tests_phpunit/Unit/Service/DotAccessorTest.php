<?php

namespace AKlump\CheckPages\Tests\Unit\Service;

use AKlump\CheckPages\Service\DotAccessor;

/**
 * @covers \AKlump\CheckPages\Service\DotAccessor
 */
class DotAccessorTest extends \PHPUnit\Framework\TestCase {

  public static function dataFortestGetProvider(): array {
    $tests = [];

    $tests[] = [
      (object) array(
        'items' =>
          array(
            0 => 'apple',
            1 => 'banana',
            2 => 'carrot',
            3 => 'daikon',
            4 => 'eggplant',
          ),
        'id' => NULL,
        'group' => '',
      ),
      'id',
      NULL,
      TRUE,
    ];

    $tests[] = [
      ["apple", "banana", "carrot", "daikon", "eggplant"],
      '',
      ["apple", "banana", "carrot", "daikon", "eggplant"],
      TRUE,
    ];

    $tests[] = [
      [
        ['id' => 144, 'title' => 'lorem ipsum'],
      ],
      0,
      ['id' => 144, 'title' => 'lorem ipsum'],
      TRUE,
    ];
    $tests[] = [
      ['foo' => ['bar' => 'baz']],
      'foo.bar',
      'baz',
      TRUE,
    ];
    $tests[] = [
      ['a' => 'apple'],
      'b',
      NULL,
      FALSE,
    ];
    $tests[] = [
      NULL,
      'a',
      NULL,
      FALSE,
    ];
    $tests[] = [
      '',
      'a',
      NULL,
      FALSE,
    ];
    $tests[] = [
      ['a' => 'apple'],
      'a',
      'apple',
      TRUE,
    ];

    return $tests;
  }

  /**
   * @dataProvider dataFortestGetProvider
   */
  public function testGetWorksOnArrays($body, $path, $expected, $has_expected) {
    $this->assertSame($expected, (new DotAccessor($body))->get($path));
    $this->assertSame($has_expected, (new DotAccessor($body))->has($path));
  }
}
