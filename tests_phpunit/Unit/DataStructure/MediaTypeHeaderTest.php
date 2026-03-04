<?php

namespace AKlump\CheckPages\Tests\Unit\DataStructure;

use AKlump\CheckPages\DataStructure\MediaTypeHeader;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\DataStructure\MediaTypeHeader
 * @uses \AKlump\CheckPages\DataStructure\HttpHeader
 * @uses \AKlump\CheckPages\Helpers\NormalizeHeaders
 */
class MediaTypeHeaderTest extends TestCase {

  public function testMediaTypesAreSplitAndStripped() {
    $h = new MediaTypeHeader('Accept', 'text/html; charset=utf-8, application/json');
    $this->assertSame(['text/html', 'application/json'], $h->getMediaTypes());
    $this->assertSame('text/html', (string) $h);
  }

  public function testMultipleLinesAreHandled() {
    $h = new MediaTypeHeader('Accept', ['text/html', 'application/json; q=0.9']);
    $this->assertSame(['text/html', 'application/json'], $h->getMediaTypes());
  }

  public function testEmptyValuesAreFiltered() {
    $h = new MediaTypeHeader('Accept', ' , text/html, ');
    $this->assertSame(['text/html'], $h->getMediaTypes());
  }

  public function testGenericHeaderLine() {
    $h = new MediaTypeHeader('Date', 'Tue, 15 Nov 1994 08:12:31 GMT');
    // NOTE: This actually demonstrates why MediaTypeHeader should ONLY be used
    // for headers that follow the media-type-ish format. Date headers have commas.
    $this->assertSame(['Tue', '15 Nov 1994 08:12:31 GMT'], $h->getMediaTypes());
  }
}
