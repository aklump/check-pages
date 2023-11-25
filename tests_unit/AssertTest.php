<?php

namespace AKlump\CheckPages\Tests\Unit;

use AKlump\CheckPages\Assert;
use PHPUnit\Framework\TestCase;

/**
 * @group default
 * @covers \AKlump\CheckPages\Assert
 */
final class AssertTest extends TestCase {

  /**
   * Provides data for testEquals.
   */
  public function dataForTestEqualsProvider() {
    $tests = [];

    $tests[] = [TRUE, '', NULL];
    $tests[] = [TRUE, '', ''];
    $tests[] = [TRUE, NULL, NULL];
    $tests[] = [TRUE, 0, 0];
    $tests[] = [TRUE, 0, 0.0];
    $tests[] = [TRUE, 0, '0'];
    $tests[] = [FALSE, NULL, 0];
    $tests[] = [FALSE, NULL, 0.0];
    $tests[] = [FALSE, NULL, '0'];
    $tests[] = [FALSE, '', '0'];
    $tests[] = [FALSE, '', 0.0];
    $tests[] = [FALSE, '', 0];

    return $tests;
  }

  /**
   * @dataProvider dataForTestEqualsProvider
   */
  public function testEquals($expect, $a, $b) {
    $test = $this->createMock(\AKlump\CheckPages\Parts\Test::class);
    $assert = new Assert('', [], $test);
    $assert->setAssertion(Assert::ASSERT_EQUALS, $a);
    $assert->setHaystack([$b]);
    $assert->run();
    $this->assertSame($expect, $assert->hasPassed());
  }

  public function testSetGetNeedle() {
    $test = $this->createMock(\AKlump\CheckPages\Parts\Test::class);
    $assert = new Assert('', [], $test);
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

