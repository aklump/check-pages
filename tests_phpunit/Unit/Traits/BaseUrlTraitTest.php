<?php

namespace AKlump\CheckPages\Tests\Unit\Traits;

use AKlump\CheckPages\Traits\BaseUrlTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\Tests\Unit\Traits\BaseUrlTraitTestable
 * @uses   \AKlump\CheckPages\Traits\BaseUrlTrait
 */
class BaseUrlTraitTest extends TestCase {

  public function testWithForeignUrl() {
    $obj = new \AKlump\CheckPages\Tests\Unit\Traits\BaseUrlTraitTestable();
    $obj->setBaseUrl('https://www.foo.com/');
    $this->assertSame('http://github.com', $obj->withBaseUrl('http://github.com'));
    $this->assertSame('http://github.com', $obj->withoutBaseUrl('http://github.com'));
  }

  public function testProtocolMismatchReturnsTheArgument() {
    $obj = new \AKlump\CheckPages\Tests\Unit\Traits\BaseUrlTraitTestable();
    $obj->setBaseUrl('https://www.foo.com/');
    $this->assertSame('http://www.foo.com/foo.php', $obj->withoutBaseUrl('http://www.foo.com/foo.php'));
  }

  public function testCreateAbsoluteUrl() {
    $obj = new \AKlump\CheckPages\Tests\Unit\Traits\BaseUrlTraitTestable();
    $obj->setBaseUrl('https://www.foo.com/');
    $this->assertSame('https://www.foo.com/foo.php', $obj->withBaseUrl('/foo.php'));

    // Assert already absolute with the base URL.
    $this->assertSame('https://www.foo.com/foo.php', $obj->withBaseUrl('https://www.foo.com/foo.php'));

    // Assert different scheme
    $this->assertSame('http://www.foo.com/foo.php', $obj->withBaseUrl('http://www.foo.com/foo.php'));
    // Assert different host.
    $this->assertSame('https://trees.com/index.php', $obj->withBaseUrl('https://trees.com/index.php'));
  }

  public function testWithEmptyBaseUrl() {
    $obj = new \AKlump\CheckPages\Tests\Unit\Traits\BaseUrlTraitTestable();
    $this->assertSame('/foo.php', $obj->withoutBaseUrl('/foo.php'));
    $this->assertSame('/foo.php', $obj->withBaseUrl('/foo.php'));
  }

  public function testCreateLocalUrl() {
    $obj = new \AKlump\CheckPages\Tests\Unit\Traits\BaseUrlTraitTestable();
    $obj->setBaseUrl('https://www.foo.com/');
    $this->assertSame('/foo.php', $obj->withoutBaseUrl('/foo.php'));
    $this->assertSame('/foo.php', $obj->withoutBaseUrl('https://www.foo.com/foo.php'));
  }

  public function testGetBaseUrlStripsTrailingSlash() {
    $obj = new \AKlump\CheckPages\Tests\Unit\Traits\BaseUrlTraitTestable();
    $obj->setBaseUrl('https://www.foo.com/');
    $this->assertSame('https://www.foo.com', $obj->getBaseUrl());
  }

}

class BaseUrlTraitTestable {

  use BaseUrlTrait;
}
