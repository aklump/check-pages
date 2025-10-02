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
}

class TestableRequestDriver extends RequestDriver {

  public function request(array $assertions = []): RequestDriverInterface {
    // TODO: Implement request() method.
  }

}
