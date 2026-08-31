<?php

namespace AKlump\CheckPages\Tests\Unit\Parts;

use AKlump\CheckPages\Command\RunCommand;
use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Parts\Suite;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @group default
 * @covers \AKlump\CheckPages\Parts\Suite
 * @uses   \AKlump\CheckPages\Parts\Runner
 * @uses   \AKlump\CheckPages\Traits\HasConfigTrait
 * @uses   \AKlump\CheckPages\Variables
 * @uses   \AKlump\CheckPages\Parts\Test
 */
final class SuiteTest extends TestCase {

  private Runner $runner;

  public static function dataFortestParsePathProvider(): array {
    $tests = [];
    $tests[] = [
      'suites/HEAD/*',
      ['group' => 'HEAD', 'id' => '*'],
    ];
    $tests[] = [
      '/FooBar/Baz/site/app_support/tests_check_pages/suites/chat.yml',
      ['group' => 'suites', 'id' => 'chat'],
    ];
    $tests[] = [
      'suites/chat.yml',
      ['group' => '', 'id' => 'chat'],
    ];
    $tests[] = [
      'suites/homepage/chat.yml',
      ['group' => 'homepage', 'id' => 'chat'],
    ];

    return $tests;
  }

  /**
   * @dataProvider dataFortestParsePathProvider
   */
  public function testParsePath(string $subject, array $expected) {
    $result = Suite::parsePath($subject);
    $this->assertSame($expected, $result);
  }

  public function testGetConfigReturnsArrayOfTestConfigs() {
    $suite = new Suite('foo', $this->runner);
    $this->assertSame([], $suite->getConfig());

    $suite = new Suite('foo', $this->runner);
    $suite->addTestByConfig(['why' => 'Just because']);
    $this->assertSame([
      ['why' => 'Just because'],
    ], $suite->getConfig());
  }

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
    $suite = new Suite($id, $this->runner);
    $suite->setGroup($group);
    $this->assertSame($control, $suite->toFilepath());
  }

  public function testNewSuiteHasRunnerConfigVariables() {
    $suite = new Suite('', $this->runner);
    $this->assertSame(123, $suite->variables()->getItem('alpha'));
    $this->assertSame(456, $suite->variables()->getItem('bravo'));
  }

  public function testAddTestByConfigAssignsGloballyUniqueNumbers(): void {
    $suite1 = new Suite('foo', $this->runner);
    $suite1->addTestByConfig(['url' => '/a']);
    $suite1->addTestByConfig(['url' => '/b']);

    $suite2 = new Suite('bar', $this->runner);
    $suite2->addTestByConfig(['url' => '/c']);

    $numbers = array_map(fn($t) => $t->getNumber(), array_merge($suite1->getTests(), $suite2->getTests()));
    $this->assertSame([1, 2, 3], $numbers);
  }

  public function testSuiteGetSetNumber(): void {
    $suite = new Suite('foo', $this->runner);
    $suite->setNumber(7);
    $this->assertSame(7, $suite->getNumber());
  }

  public function setUp(): void {
    RunCommand::resetSequences();
    $input = $this->getMockBuilder(InputInterface::class)
      ->getMock();
    $output = $this->getMockBuilder(OutputInterface::class)
      ->getMock();

    $this->runner = new Runner($input, $output);
    $this->runner->setConfig(['variables' => ['alpha' => 123, 'bravo' => 456]]);
  }

  public function tearDown(): void {
    RunCommand::resetSequences();
  }

}
