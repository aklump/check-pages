<?php

use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Parts\Suite;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @group default
 * @covers \AKlump\CheckPages\Parts\Runner
 */
final class RunnerTest extends TestCase {

  /**
   * Provides data for testAddSuiteIdFilter.
   */
  public function dataForTestAddSuiteIdFilterProvider() {
    $tests = [];
    $tests[] = [
      'lorem.yml',
      'lorem.yaml',
      'foo,bar',
    ];

    return $tests;
  }

  public function testAddSuiteFilterAllowsDots() {
    $runner = $this->getRunner();
    $result = $runner->addSuiteIdFilter('live.canonical');
    $this->assertSame($runner, $result);
  }

  /**
   * @dataProvider dataForTestAddSuiteIdFilterProvider
   */
  public function testAddSuiteIdFilterThrowsBadValues(string $filter) {
    $this->expectException(\InvalidArgumentException::class);
    $this->getRunner()->addSuiteIdFilter($filter);
  }

  public function testSetKeyValuePairReturnsMessageAndSetsAsExpected() {
    $suite = new Suite('test', [], $this->getRunner());
    $message = $suite->getRunner()
      ->setKeyValuePair($suite->variables(), 'foo', 'bar');
    $this->assertIsString($message);
    $this->assertStringContainsString('foo', $message);
    $this->assertStringContainsString('bar', $message);
    $this->assertSame('bar', $suite->variables()->getItem('foo'));
  }

  private function getRunner(): Runner {
    $input = $this->getMockBuilder(InputInterface::class)
      ->getMock();
    $output = $this->getMockBuilder(OutputInterface::class)
      ->getMock();

    return new Runner($input, $output);
  }
}
