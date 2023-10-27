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

  public function dataFortestAddFiltersWorkIfYamlExtensionIsUsedProvider() {
    $tests = [];
    $tests[] = ['.yaml'];
    $tests[] = ['.yml'];

    return $tests;
  }

  /**
   * @dataProvider dataFortestAddFiltersWorkIfYamlExtensionIsUsedProvider
   */
  public function testAddFiltersWorkIfYamlExtensionIsUsed(string $extension) {
    $runner = $this->getRunner();
    $runner->addFilter('lorem' . $extension);
    $suites = new \AKlump\CheckPages\SuiteCollection([
      new Suite('lorem', [], $runner),
    ]);
    $result = $runner->applyFilters($suites);
    $this->assertSame('lorem', $result->first()->id());
  }


  public function testAddFilterAllowsDots() {
    $runner = $this->getRunner();
    $result = $runner->addFilter('live.canonical');
    $this->assertSame($runner, $result);
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
