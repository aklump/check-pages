<?php

namespace AKlump\CheckPages\Tests\Unit\Parts;

use AKlump\CheckPages\Output\Icons;
use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\TAP\Directive;
use AKlump\CheckPages\TAP\Result;
use AKlump\CheckPages\TAP\TestInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @covers \AKlump\CheckPages\Parts\Test
 * @uses   \AKlump\CheckPages\Parts\Runner
 * @uses   \AKlump\CheckPages\Parts\Suite
 * @uses   \AKlump\CheckPages\Variables
 */
final class TestTest extends TestCase {

  private Runner $runner;

  public function testBadgesAppearInDescription() {
    $test = $this->getTestInstance();
    $test->addBadge(Icons::CLOCK);
    $test->addBadge(Icons::FOOTBALL);
    $this->assertStringContainsString(trim(Icons::CLOCK), $test->getDescription());
    $this->assertStringContainsString(trim(Icons::FOOTBALL), $test->getDescription());
  }

  public function testNoWhyNoUrlReturnsEmptyStringDescriptionWhenMultipleHttpMethodsInSuite() {
    $suite = new Suite('', $this->runner);
    $suite->addTestByConfig([
      'request' => ['method' => 'post'],
    ]);
    $suite->addTestByConfig([]);
    $test = new Test(1, [], $suite);
    $this->assertEmpty($test->getDescription());
  }

  public function testNoWhyNoUrlReturnsEmptyStringDescription() {
    $test = $this->getTestInstance(1, []);
    $this->assertEmpty($test->getDescription());
  }

  public function testGetDescriptionInterpolatesWhy() {
    $test = $this->getTestInstance(1, [
      'why' => '${token} ipsum',
    ]);
    $test->getSuite()->variables()->setItem('token', 'lorem');
    $this->assertSame('lorem ipsum', $test->getDescription());
  }

  /**
   * Provides data for testGetDescriptionAlwaysReturnsWhy.
   */
  public function dataForTestGetDescriptionAlwaysReturnsWhyProvider() {
    $tests = [];
    $tests[] = [[]];
    $tests[] = [
      [
        'url' => '/index.php',
        ['request' => ['method' => 'post']],
      ],
    ];

    return $tests;
  }

  /**
   * @dataProvider dataForTestGetDescriptionAlwaysReturnsWhyProvider
   */
  public function testGetDescriptionAlwaysReturnsWhy(array $config) {
    $test = $this->getTestInstance(1, [
        'why' => 'foobar',
      ] + $config);
    $this->assertSame('foobar', $test->getDescription());
  }

  public function testSetWhy() {
    $test = $this->getTestInstance(1, ['why' => 'foobar']);
    $this->assertSame('foobar', $test->get('why'));
    $test->set('why', 'lorem');
    $this->assertSame('lorem', $test->get('why'));
  }

  public function testGetRelativeUrl() {
    $test = $this->getTestInstance(1, ['url' => '/foo/bar']);
    $this->assertTrue($test->has('url'));
    $this->assertSame('/foo/bar', $test->get('url'));
  }

  public function testGetHttpMethod() {
    $test = $this->getTestInstance();
    $this->assertSame('GET', $test->getHttpMethod());
    $test->setConfig(['request' => ['method' => 'post']]);
    $this->assertSame('POST', $test->getHttpMethod());
  }

  public function testTestImplementsTestInterface() {
    $test = $this->getTestInstance();
    $this->assertInstanceOf(TestInterface::class, $test);
  }

  public function testSetDescriptionOverridesConfigWhy() {
    $test = $this->getTestInstance(1, ['why' => 'original why']);
    $this->assertSame('original why', $test->getDescription());

    $result = $test->setDescription('custom description');
    $this->assertSame($test, $result);
    $this->assertSame('custom description', $test->getDescription());
  }

  public function testGetAndSetDirective() {
    $test = $this->getTestInstance();
    $this->assertSame(Directive::NONE, $test->getDirective());

    $result = $test->setDirective(Directive::SKIP);
    $this->assertSame($test, $result);
    $this->assertSame(Directive::SKIP, $test->getDirective());

    $test->setDirective(Directive::TODO);
    $this->assertSame(Directive::TODO, $test->getDirective());
  }

  public function testGetAndSetNumber() {
    $test = $this->getTestInstance(5);
    $this->assertSame(5, $test->getNumber());

    $result = $test->setNumber(42);
    $this->assertSame($test, $result);
    $this->assertSame(42, $test->getNumber());
  }

  public function testGetAndSetResult() {
    $test = $this->getTestInstance();
    $this->assertSame(Result::NONE, $test->getResult());

    $result = $test->setResult(Result::OK);
    $this->assertSame($test, $result);
    $this->assertSame(Result::OK, $test->getResult());

    $test->setResult(Result::NOT_OK);
    $this->assertSame(Result::NOT_OK, $test->getResult());
  }

  /**
   * Get a new Test class instance with mocked dependencies.
   *
   * @param int $number
   * @param array $config
   *
   * @return \AKlump\CheckPages\Parts\Test
   */
  public function getTestInstance(int $number = 1, array $config = []) {
    $suite = new Suite('', $this->runner);

    return new Test($number, $config, $suite);
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
