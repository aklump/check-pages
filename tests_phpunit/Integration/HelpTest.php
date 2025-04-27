<?php

namespace AKlump\CheckPages\Tests\Unit;

use AKlump\CheckPages\Help;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\Help
 */
class HelpTest extends TestCase {

  public function testConstructor() {
    $help = new Help(202, 'lorem ipsum', ['foo', 'bar', 'baz']);
    $this->assertSame('202', $help->code());
    $this->assertSame('lorem ipsum', $help->description());
    $this->assertSame(['foo', 'bar', 'baz'], $help->examples());
  }

}
