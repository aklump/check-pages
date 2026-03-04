<?php

namespace Event;

use AKlump\CheckPages\Event\HttpMessageEvent;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\Event\HttpMessageEvent
 * @uses \AKlump\CheckPages\Helpers\NormalizeHeaders
 */
class HttpMessageEventTest extends TestCase {

  public function testJsonBodyWorksAsExpected() {
    $event = new HttpMessageEvent([
      'content-type' => ['application/json'],
    ], '{"foo": "bar"}', 200);
    $this->assertSame('{"foo": "bar"}', $event->getBody());
  }

  public function testCanCreateWithoutTest() {
    $event = new HttpMessageEvent([], '', 200);
    $this->assertSame([], $event->getHeaders());
    $this->assertSame('', $event->getBody());
    $this->assertSame(200, $event->getStatusCode());
    $this->assertNull($event->getTest());
  }

  public function testHeadersAreNormalized() {
    $event = new HttpMessageEvent(['FOO' => 'bar'], '', 0);
    $this->assertSame(['foo' => ['bar']], $event->getHeaders());
  }

}
