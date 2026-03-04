<?php

namespace AKlump\CheckPages\Tests\Unit\DataStructure;

use AKlump\CheckPages\DataStructure\ContentTypeHeader;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\DataStructure\ContentTypeHeader
 */
class ContentTypeHeaderTest extends TestCase {

  public static function dataFortestNormalizeProvider(): array {
    $tests = [];
    $tests[] = [
      'application/something+yaml',
      'application/yaml',
    ];

    return $tests;
  }

  /**
   * @dataProvider dataFortestNormalizeProvider
   */
  public function testNormalize($input, string $expected) {
    $this->assertSame($expected, (string) (new ContentTypeHeader($input))->normalize());

  }

  public function testHandlesSemiColons() {
    $ct = new ContentTypeHeader('text/html; charset=utf-8, application/xhtml+xml; charset=utf-8, application/xml; charset=utf-8');
    $this->assertCount(3, $ct->get());
    $this->assertSame('text/html', $ct->get()[0]);
    $this->assertSame('application/xhtml+xml', $ct->get()[1]);
    $this->assertSame('application/xml', $ct->get()[2]);
    $this->assertSame('text/html', (string) $ct);
  }

  public function testGet() {
    $ct = new ContentTypeHeader('text/html, application/xhtml+xml, application/xml');
    $this->assertCount(3, $ct->get());
    $this->assertSame('text/html', $ct->get()[0]);
    $this->assertSame('application/xhtml+xml', $ct->get()[1]);
    $this->assertSame('application/xml', $ct->get()[2]);
  }

  public function testTwoStringReturnsTheFirstOfMultiple() {
    $ct = new ContentTypeHeader('text/html, application/xhtml+xml, application/xml');
    $this->assertSame('text/html', (string) $ct);
  }
}
