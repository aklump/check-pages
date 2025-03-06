<?php

namespace AKlump\CheckPages\Tests\Unit\Parts;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\DriverEvent;
use AKlump\CheckPages\Files\LocalFilesProvider;
use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\SuiteCollection;
use AKlump\CheckPages\Tests\Unit\TestWithDispatcherTrait;
use AKlump\CheckPages\Tests\Unit\TestWithFilesTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @group default
 * @covers \AKlump\CheckPages\Parts\Runner
 * @uses   \AKlump\CheckPages\Files\LocalFilesProvider
 * @uses   \AKlump\CheckPages\Helpers\FilterSuites
 * @uses   \AKlump\CheckPages\Parts\Suite
 * @uses   \AKlump\CheckPages\SuiteCollection
 * @uses   \AKlump\CheckPages\Traits\HasRunnerTrait
 * @uses   \AKlump\CheckPages\Variables
 * @uses   \AKlump\CheckPages\Event\DriverEvent
 * @uses   \AKlump\CheckPages\Event\SuiteEvent
 * @uses   \AKlump\CheckPages\Event\TestEvent
 * @uses   \AKlump\CheckPages\Output\VerboseDirective
 * @uses   \AKlump\CheckPages\Parts\Test
 * @uses   \AKlump\CheckPages\Parts\TestRunner
 * @uses   \AKlump\CheckPages\Browser\RequestDriver
 * @uses   \AKlump\CheckPages\Event\RunnerEvent
 * @uses   \AKlump\CheckPages\Output\ConsoleEchoPrinter
 * @uses   \AKlump\CheckPages\Output\LoggerPrinter
 * @uses   \AKlump\CheckPages\Output\MultiPrinter
 * @uses   \AKlump\CheckPages\Service\DispatcherFactory
 */
final class RunnerTest extends TestCase {

  use TestWithDispatcherTrait;
  use TestWithFilesTrait;

  public function testDynamicallyAddedTestIsRun() {
    $runner = $this->getRunner();
    $dispatcher = $runner->getDispatcher();
    $dispatcher->addListener(Event::TEST_FINISHED, function (DriverEvent $event) {
      $suite = $event->getTest()->getSuite();
      if (count($suite->getTests()) < 2) {
        $suite->addTestByConfig([
          'why' => 'Add a dynamic test to be able to test it gets run.',
        ]);
      }
    });
    $suite = new Suite(__FUNCTION__, $runner);
    $suite->addTestByConfig([
      'set' => 'title',
      'value' => 'lorem',
    ]);
    $runner->run($suite, 'suite.yml');
    $this->assertSame(2, $runner->getTotalTestsRun());
  }

  public function testGetTotalMethods() {
    $runner = $this->getRunner();
    $suite = new Suite(__FUNCTION__, $runner);
    $suite->addTestByConfig([
      'set' => 'title',
      'value' => 'lorem',
    ]);
    $suite->addTestByConfig([
      'set' => 'subtitle',
      'value' => 'ipsum',
    ]);
    $runner->run($suite, 'suite.yml');
    $this->assertSame(2, $runner->getTotalTestsRun());
    $this->assertSame(0, $runner->getTotalFailedTests());
    $this->assertSame(0, $runner->getTotalAssertionsRun());
    $this->assertSame(0, $runner->getTotalFailedAssertions());
  }

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
    $suites = new SuiteCollection([
      new Suite('lorem', $runner),
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
    $suite = new Suite('test', $this->getRunner());
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
    $runner = new Runner($input, $output);
    $root_files = new LocalFilesProvider(__DIR__ . '/../../');
    $runner->setFiles($root_files);

    return $runner;
  }
}
