<?php

use AKlump\CheckPages\Variables;
use PHPUnit\Framework\TestCase;

/**
 * @group default
 * @covers \Drupal\gop_theme\Tests\Foo
 */
final class VariablesTest extends TestCase {

  public function testStrangeBugWithZeroIndexInterpolation() {
    $var = new Variables();
    $var->setItem('loop.index0', 0);
    $subject = ['find' => [['dom' => '.foo${loop.index0}']]];
    $subject = $var->interpolate($subject);
    $this->assertSame('.foo0', $subject['find'][0]['dom']);
  }

  public function testInterpolateStringWithInt() {
    $var = new Variables();
    $this->assertSame($var, $var->setItem('age', 47));
    $subject = '${age}';
    $subject = $var->interpolate($subject);
    $this->assertSame(47, $subject);
  }

  public function testInterpolateNoInterpolationArray() {
    $var = new Variables();
    $var->setItem('data', ['18:32', 'Apr 7']);
    $subject = 'location: Office';
    $subject = $var->interpolate($subject);
    $this->assertSame('location: Office', $subject);
  }

  public function testInterpolateNoInterpolation() {
    $var = new Variables();
    $var->setItem('time', '18:32');
    $subject = 'date: Apr 7';
    $subject = $var->interpolate($subject);
    $this->assertSame('date: Apr 7', $subject);
  }

  public function testLoopValueWithArray() {
    $var = new Variables();
    $var->setItem('loop.value', ['/admin', 403, 'No Access']);
    $subject = 'expect: ${loop.value[1]}';
    $subject = $var->interpolate($subject);
    $this->assertSame('expect: 403', $subject);
  }

  public function testInterpolateStringWithArray() {
    $var = new Variables();
    $this->assertSame($var, $var->setItem('person', ['Mark', 47]));

    $subject = 'name: ${person[0]}';
    $subject = $var->interpolate($subject);
    $this->assertSame('name: Mark', $subject);

    $subject = 'age: ${person[1]}';
    $subject = $var->interpolate($subject);
    $this->assertSame('age: 47', $subject);

    $subject = 'name: ${person.0}';
    $subject = $var->interpolate($subject);
    $this->assertSame('name: Mark', $subject);

    $subject = 'age: ${person.1}';
    $subject = $var->interpolate($subject);
    $this->assertSame('age: 47', $subject);
  }

  public function testInterpolateStringWithFloat() {
    $var = new Variables();
    $this->assertSame($var, $var->setItem('price', 19.95));
    $subject = '${price}';
    $subject = $var->interpolate($subject);
    $this->assertSame(19.95, $subject);
  }

  public function testInterpolateStringWithString() {
    $var = new Variables();
    $this->assertSame($var, $var->setItem('color', 'red'));
    $subject = 'color = ${color}';
    $subject = $var->interpolate($subject);
    $this->assertSame('color = red', $subject);
  }

  public function testCountAndRemoveScalars() {
    $var = new Variables();
    $this->assertSame($var, $var->setItem('foo', 'bar'));
    $this->assertSame(1, $var->count());
    $this->assertSame(0, $var->removeItem('foo')->count());
  }

  public function testSetAndGetScalars() {
    $var = new Variables();
    $this->assertSame($var, $var->setItem('foo', 'bar'));
    $this->assertSame('bar', $var->getItem('foo'));
    $this->assertSame(1, $var->count());
  }
}
