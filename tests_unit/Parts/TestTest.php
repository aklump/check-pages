<?php

namespace AKlump\CheckPages\Tests\Unit\Parts;

use AKlump\CheckPages\Output\Icons;
use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\Parts\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @covers \AKlump\CheckPages\Parts\Test
 */
final class TestTest extends TestCase {

  public function testBadgesAppearInDescription() {
    $test = $this->getTestInstance();
    $test->addBadge(Icons::CLOCK);
    $test->addBadge(Icons::FOOTBALL);
    $this->assertStringContainsString(trim(Icons::CLOCK), $test->getDescription());
    $this->assertStringContainsString(trim(Icons::FOOTBALL), $test->getDescription());
  }

  public function testNoWhyNoUrlReturnsEmptyStringDescriptionWhenMultipleHttpMethodsInSuite() {
    $suite = new Suite('', [], $this->runner);
    $suite->addTestByConfig([
      'request' => ['method' => 'post'],
    ]);
    $suite->addTestByConfig([]);
    $test = new Test('', [], $suite);
    $this->assertEmpty($test->getDescription());
  }

  public function testNoWhyNoUrlReturnsEmptyStringDescription() {
    $test = $this->getTestInstance('', []);
    $this->assertEmpty($test->getDescription());
  }

  public function testGetDescriptionInterpolatesWhy() {
    $test = $this->getTestInstance('', [
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
    $test = $this->getTestInstance('', [
        'why' => 'foobar',
      ] + $config);
    $this->assertSame('foobar', $test->getDescription());
  }

  public function testSetWhy() {
    $test = $this->getTestInstance('', ['why' => 'foobar']);
    $this->assertSame('foobar', $test->get('why'));
    $test->set('why', 'lorem');
    $this->assertSame('lorem', $test->get('why'));
  }

  public function testGetRelativeUrl() {
    $test = $this->getTestInstance('', ['url' => '/foo/bar']);
    $this->assertTrue($test->has('url'));
    $this->assertSame('/foo/bar', $test->get('url'));
  }

  public function testGetHttpMethod() {
    $test = $this->getTestInstance();
    $this->assertSame('GET', $test->getHttpMethod());
    $test->setConfig(['request' => ['method' => 'post']]);
    $this->assertSame('POST', $test->getHttpMethod());
  }

  /**
   * Get a new Test class instance with mocked dependencies.
   *
   * @param $id
   * @param $config
   *
   * @return \AKlump\CheckPages\Parts\Test
   */
  public function getTestInstance(string $id = '', array $config = []) {
    $suite = new Suite('', [], $this->runner);

    return new Test($id, $config, $suite);
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
