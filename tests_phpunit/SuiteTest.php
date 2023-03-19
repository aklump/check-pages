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

  public function testNewSuiteHasRunnerConfigVariables() {
    $input = $this->getMockBuilder(InputInterface::class)
      ->getMock();
    $output = $this->getMockBuilder(OutputInterface::class)
      ->getMock();

    $runner = new Runner($input, $output);
    $runner->setConfig(['variables' => ['alpha' => 123, 'bravo' => 456]]);

    $suite = new Suite('', [], $runner);
    $this->assertSame(123, $suite->variables()->getItem('alpha'));
    $this->assertSame(456, $suite->variables()->getItem('bravo'));
  }

}
