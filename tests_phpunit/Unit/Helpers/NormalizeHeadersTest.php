<?php

namespace AKlump\CheckPages\Tests\Unit\Helpers;

use AKlump\CheckPages\Helpers\NormalizeHeaders;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\Helpers\NormalizeHeaders
 */
class NormalizeHeadersTest extends TestCase {

  public function testCSVHeadersSplit() {
    $headers = (new NormalizeHeaders())(['content-type' => 'text/html, application/xhtml+xml, application/xml']);
    $this->assertSame([
      'text/html, application/xhtml+xml, application/xml',
    ], $headers['content-type']);
  }

  public function testHeaderObjectValueThrows() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Headers must be an array of strings.');
    (new NormalizeHeaders())([
      'foo' => (object) ['lorem'],
    ]);
  }

  public function testAssociateArrayValueThrows() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Header values must be numerically indexed arrays, not associative arrays.');
    (new NormalizeHeaders())([
      'foo' => ['lorem' => 'ipsum'],
    ]);
  }

  public static function dataFortestInvokeProvider(): array {
    $tests = [];
    $tests[] = [
      ['Status' => 200],
      ['status' => ['200']],
    ];
    $tests[] = [
      ['X-ages' => [35, 40]],
      ['x-ages' => ['35', '40']],
    ];
    $tests[] = [
      ['STATUS' => '200 OK', 'CONTENT-TYPE' => 'text/html; charset=UTF-8'],
      ['content-type' => ['text/html; charset=UTF-8'], 'status' => ['200 OK']],
    ];
    $tests[] = [
      ['content-type' => ['text/html; charset=UTF-8'], 'status' => ['200 OK']],
      ['content-type' => ['text/html; charset=UTF-8'], 'status' => ['200 OK']],
    ];

    return $tests;
  }

  /**
   * @dataProvider dataFortestInvokeProvider
   */
  public function testInvoke(array $subject, array $expected) {
    $normalized = (new NormalizeHeaders())($subject);
    $this->assertSame($expected, $normalized);
    $this->assertSame($expected, (new NormalizeHeaders())($normalized), 'The second call should not change the result.');
  }
}
