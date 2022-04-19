<?php

use AKlump\CheckPages\Assert;
use PHPUnit\Framework\TestCase;

/**
 * @group default
 * @covers \Drupal\gop_theme\Tests\Foo
 */
final class AssertTest extends TestCase {

  public function testSetGetNeedle() {
    $assert = new Assert([], '');
    $this->assertNull($assert->getNeedle());
    $return = $assert->setNeedleIfNotSet('foo');
    $this->assertSame($assert, $return);

    $this->assertSame('foo', $assert->getNeedle());
    $assert->setNeedleIfNotSet('bar');
    $this->assertSame('foo', $assert->getNeedle());

    $return = $assert->setNeedle('bar');
    $this->assertSame($assert, $return);
    $this->assertSame('bar', $assert->getNeedle());
  }

}
