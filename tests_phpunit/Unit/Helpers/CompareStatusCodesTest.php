<?php

namespace AKlump\CheckPages\Tests\Unit\Helpers;

use AKlump\CheckPages\Helpers\CompareStatusCodes;
use AKlump\CheckPages\Helpers\ComposerStatusCodes;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\Helpers\CompareStatusCodes
 */
class CompareStatusCodesTest extends TestCase {

  public static function dataFortestInvokeProvider(): array {
    $tests = [];
    $tests[] = [
      1,
      '4xx',
      300,
    ];
    $tests[] = [
      0,
      '4xx',
      404,
    ];
    $tests[] = [
      0,
      200,
      '2xx',
    ];
    $tests[] = [
      -1,
      200,
      301,
    ];
    $tests[] = [
      1,
      '404',
      301,
    ];
    $tests[] = [
      0,
      '',
      '',
    ];

    return $tests;
  }

  /**
   * @dataProvider dataFortestInvokeProvider
   */
  public function testInvoke(int $expected, $a, $b) {
    $result = (new CompareStatusCodes())($a, $b);
    $this->assertSame($expected, $result);
  }

}
