<?php

use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Parts\Suite;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @group default
 * @covers \AKlump\CheckPages\Parts\Suite
 */
final class SuiteTest extends TestCase {

  /**
   * Provides data for testToFilepath.
   */
  public function dataForTestToFilepathProvider() {
    $tests = [];
    $tests[] = [
      '',
      'lorem',
      'lorem',
    ];
    $tests[] = [
      'foo',
      'lorem',
      'foo/lorem',
    ];
    $tests[] = [
      'foo bar/baz',
      'lorem.ipsum-dolar',
      'foo_bar_baz/lorem_ipsum_dolar',
    ];

    return $tests;
  }

  /**
   * @dataProvider dataForTestToFilepathProvider
   */
  public function testToFilepath($group, $id, $control) {
    $suite = new Suite($id, [], $this->runner);
    $suite->setGroup($group);
    $this->assertSame($control, $suite->toFilepath());
  }

  public function testNewSuiteHasRunnerConfigVariables() {
    $suite = new Suite('', [], $this->runner);
    $this->assertSame(123, $suite->variables()->getItem('alpha'));
    $this->assertSame(456, $suite->variables()->getItem('bravo'));
  }

  public function setUp(): void {
    $input = $this->getMockBuilder(InputInterface::class)
      ->getMock();
    $output = $this->getMockBuilder(OutputInterface::class)
      ->getMock();

    $this->runner = new Runner($input, $output);
    $this->runner->setConfig(['variables' => ['alpha' => 123, 'bravo' => 456]]);
  }

}
