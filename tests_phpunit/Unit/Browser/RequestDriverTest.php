<?php

namespace AKlump\CheckPages\Tests\Unit\Browser;

use AKlump\CheckPages\Browser\RequestDriver;
use AKlump\CheckPages\Browser\RequestDriverInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @covers \AKlump\CheckPages\Browser\RequestDriver::setHeader
 */
class RequestDriverTest extends TestCase {

  public function testGetHeaderLine() {
    $driver = new TestableRequestDriver($this->createMock(EventDispatcher::class));
    $driver->setHeader('fOo', ['bar', 'baz']);
    $this->assertSame('bar,baz', $driver->getHeaderLine('FOO'));
    $this->assertSame('bar,baz', $driver->getHeaderLine('foo'));
  }

  public function testGetHeader() {
    $driver = new TestableRequestDriver($this->createMock(EventDispatcher::class));
    $driver->setHeader('fOo', ['bar', 'baz']);
    $this->assertSame(['bar', 'baz'], $driver->getHeader('FOO'));
    $this->assertSame(['bar', 'baz'], $driver->getHeader('foo'));
  }

  public function testPassingStringArgument() {
    $driver = new TestableRequestDriver($this->createMock(EventDispatcher::class));
    $deprecation_message = '';
    $deprecation_triggered = FALSE;

    set_error_handler(function ($number, $message) use (&$deprecation_triggered, &$deprecation_message) {
      if ($number === E_USER_DEPRECATED) {
        $deprecation_triggered = TRUE;
        $deprecation_message = $message;
      }
    });
    $driver->setHeader('foo', 'bar');
    restore_error_handler();
    $this->assertTrue($deprecation_triggered, 'Deprecation notice was not triggered');
    $this->assertStringContainsString('RequestDriver::setHeader(string $value) is deprecated', $deprecation_message);
    $this->assertStringContainsString('RequestDriver::setHeader(array $value) instead.', $deprecation_message);
  }

  public function testSetHeaderWorksWithArrayValue() {
    $driver = new TestableRequestDriver($this->createMock(EventDispatcher::class));
    $driver->setHeader('foo', ['bar', 'baz']);
    $this->assertSame(['bar', 'baz'], $driver->getHeaders()['foo']);
  }

  public function testWithHeader() {
    $driver = new TestableRequestDriver($this->createMock(EventDispatcher::class));
    $driver->setHeader('foo', ['bar']);
    $clone = $driver->withHeader('foo', ['baz']);
    $this->assertNotSame($driver, $clone);
    $this->assertSame(['bar'], $driver->getHeader('foo'));
    $this->assertSame(['baz'], $clone->getHeader('foo'));
  }

  public function testWithAddedHeader() {
    $driver = new TestableRequestDriver($this->createMock(EventDispatcher::class));
    $driver->setHeader('foo', ['bar']);
    $clone = $driver->withAddedHeader('foo', ['baz']);
    $this->assertNotSame($driver, $clone);
    $this->assertSame(['bar'], $driver->getHeader('foo'));
    $this->assertSame(['bar', 'baz'], $clone->getHeader('foo'));
  }

  public function testWithoutHeader() {
    $driver = new TestableRequestDriver($this->createMock(EventDispatcher::class));
    $driver->setHeader('foo', ['bar']);
    $clone = $driver->withoutHeader('foo');
    $this->assertNotSame($driver, $clone);
    $this->assertTrue($driver->hasHeader('foo'));
    $this->assertFalse($clone->hasHeader('foo'));
  }

  public function testWithMethod() {
    $driver = new TestableRequestDriver($this->createMock(EventDispatcher::class));
    $driver->setMethod('GET');
    $clone = $driver->withMethod('POST');
    $this->assertNotSame($driver, $clone);
    $this->assertSame('GET', $driver->getMethod());
    $this->assertSame('POST', $clone->getMethod());
  }

  public function testWithUri() {
    $driver = new TestableRequestDriver($this->createMock(EventDispatcher::class));
    $driver->setUrl('http://example.com/foo');
    $uri = new \GuzzleHttp\Psr7\Uri('http://example.com/bar');
    $clone = $driver->withUri($uri);
    $this->assertNotSame($driver, $clone);
    $this->assertSame('http://example.com/foo', $driver->getUrl());
    $this->assertSame('http://example.com/bar', $clone->getUrl());
  }

  public function testWithProtocolVersion() {
    $driver = new TestableRequestDriver($this->createMock(EventDispatcher::class));
    $clone = $driver->withProtocolVersion('2.0');
    $this->assertNotSame($driver, $clone);
    $this->assertSame('1.1', $driver->getProtocolVersion());
    $this->assertSame('2.0', $clone->getProtocolVersion());
  }

  public function testWithBody() {
    $driver = new TestableRequestDriver($this->createMock(EventDispatcher::class));
    $body = \GuzzleHttp\Psr7\Utils::streamFor('foo');
    $clone = $driver->withBody($body);
    $this->assertNotSame($driver, $clone);
    $this->assertSame("foo\n", (string) $clone->getBody());
  }
}

class TestableRequestDriver extends RequestDriver {

  public function request(array $assertions = []): RequestDriverInterface {
    // TODO: Implement request() method.
  }

  public function getSupportedMethods(): array {
    return [];
  }


}
