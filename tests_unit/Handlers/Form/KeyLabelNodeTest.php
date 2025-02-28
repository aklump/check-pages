<?php

// TODO This file needs to move into the handler directory.
// TODO Move ./user_login_form.html as well.
namespace AKlump\CheckPages\Tests\Unit\Handlers\Form;

use AKlump\CheckPages\Handlers\Form\FormValuesManager;
use AKlump\CheckPages\Handlers\Form\KeyLabelNode;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

require_once '/Users/aaronklump/Code/Packages/cli/check-pages/app/includes/handlers/form/src/KeyLabelNode.php';

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

  public function testMutateToKey() {
    $node = new KeyLabelNode('f', 'Food');
    $value = 'f';
    $result = $node->mutateToKey($value);
    $this->assertTrue($result);
    $this->assertSame('f', $value);

    $value = 'Food';
    $result = $node->mutateToKey($value);
    $this->assertTrue($result);
    $this->assertSame('f', $value);

    $bad_value = 'bogus';
    $result = $node->mutateToKey($bad_value);
    $this->assertFalse($result);
    $this->assertSame('bogus', $bad_value);

  }

  public function testGetKey() {
    $node = new KeyLabelNode('f', 'Food');
    $this->assertSame('f', $node->getKey());
  }

}
