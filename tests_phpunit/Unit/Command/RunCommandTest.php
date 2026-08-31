<?php

namespace AKlump\CheckPages\Tests\Unit\Command;

use AKlump\CheckPages\Command\RunCommand;
use PHPUnit\Framework\TestCase;

/**
 * @group default
 * @covers \AKlump\CheckPages\Command\RunCommand
 */
final class RunCommandTest extends TestCase {

  protected function setUp(): void {
    RunCommand::resetSequences();
  }

  protected function tearDown(): void {
    RunCommand::resetSequences();
  }

  public function testNextPointNumberReturnsStrictlyIncreasingIntegers(): void {
    $a = RunCommand::nextPointNumber();
    $b = RunCommand::nextPointNumber();
    $c = RunCommand::nextPointNumber();
    $this->assertSame(1, $a);
    $this->assertSame(2, $b);
    $this->assertSame(3, $c);
  }

  public function testNextSuiteNumberReturnsStrictlyIncreasingIntegers(): void {
    $a = RunCommand::nextSuiteNumber();
    $b = RunCommand::nextSuiteNumber();
    $c = RunCommand::nextSuiteNumber();
    $this->assertSame(1, $a);
    $this->assertSame(2, $b);
    $this->assertSame(3, $c);
  }

  public function testPointAndSuiteSequencesAreIndependent(): void {
    $p1 = RunCommand::nextPointNumber();
    $s1 = RunCommand::nextSuiteNumber();
    $p2 = RunCommand::nextPointNumber();
    $s2 = RunCommand::nextSuiteNumber();
    $this->assertSame(1, $p1);
    $this->assertSame(1, $s1);
    $this->assertSame(2, $p2);
    $this->assertSame(2, $s2);
  }

}
