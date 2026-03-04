<?php

namespace AKlump\CheckPages\Tests\Unit\DataStructure;

use AKlump\CheckPages\DataStructure\HttpHeader;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\DataStructure\HttpHeader
 */
class HttpHeaderTest extends TestCase {

  public function testTwoStringReturnsTheFirstOfMultiple() {
    $ct = new HttpHeader('fOo', 'text/html, application/xhtml+xml');
    $this->assertSame('text/html', (string) $ct, 'The first value is returned.');
    $this->assertSame([
      'text/html',
      'application/xhtml+xml',
    ], $ct->get(), 'All values are returned.');;
    $this->assertSame('foo', $ct->getName(), 'The name is returned.');;
  }
}
