<?php

namespace AKlump\CheckPages\Tests\Unit\Traits;

use AKlump\CheckPages\Traits\PassFailTrait;

/**
 * @coversNothing
 */
class PassFailTraitTest extends \PHPUnit\Framework\TestCase {

  public function testSetHasPassed() {
    $obj = new PassFailTraitTestable();
    $this->assertTrue($obj->setPassed()->hasPassed());
  }

  public function testSetHasFailed() {
    $obj = new PassFailTraitTestable();
    $this->assertTrue($obj->setFailed()->hasFailed());
  }

  public function testClearResult() {
    $obj = new PassFailTraitTestable();

    $obj->setPassed();
    $obj->clearResult();
    $this->assertFalse($obj->hasPassed());
    $this->assertFalse($obj->hasFailed());

    $obj->setFailed();
    $obj->clearResult();
    $this->assertFalse($obj->hasPassed());
    $this->assertFalse($obj->hasFailed());
  }

}

class PassFailTraitTestable {

  use PassFailTrait;
}
