<?php

namespace AKlump\CheckPages\Tests\Unit\DataStructure;

use AKlump\CheckPages\DataStructure\HttpHeader;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\DataStructure\HttpHeader
 * @uses \AKlump\CheckPages\Helpers\NormalizeHeaders
 */
class HttpHeaderTest extends TestCase {

  public function testRawHeadersAreNotSplit() {
    $ct = new HttpHeader('fOo', 'text/html, application/xhtml+xml');
    $this->assertSame('text/html, application/xhtml+xml', (string) $ct, 'The raw value is returned.');
    $this->assertSame([
      'text/html, application/xhtml+xml',
    ], $ct->getLines(), 'All lines are returned exactly as normalized.');
    $this->assertSame('foo', $ct->getName(), 'The name is returned.');
  }

  public function testMultipleHeaderLinesArePreserved() {
    $h = new HttpHeader('X-Multiple', ['Line 1', 'Line 2']);
    $this->assertSame(['Line 1', 'Line 2'], $h->getLines());
  }
}
