<?php

// TODO This file needs to move into the handler directory.
// TODO Move ./user_login_form.html as well.
namespace AKlump\CheckPages\Tests\Unit\Handlers\Form;

use AKlump\CheckPages\Handlers\Form\KeyLabelNode;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\Handlers\Form\KeyLabelNode
 */
class KeyLabelNodeTest extends TestCase {

  public function testToString() {
    $node = new KeyLabelNode('f', 'Food');
    $this->assertSame('f|Food', (string) $node);
  }

  public function testGeyLabel() {
    $node = new KeyLabelNode('f', 'Food');
    $this->assertSame('Food', $node->getLabel());
  }

  public function testGetKey() {
    $node = new KeyLabelNode('f', 'Food');
    $this->assertSame('f', $node->getKey());
  }

}
