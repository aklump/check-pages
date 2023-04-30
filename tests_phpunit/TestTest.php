<?php

use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Parts\Suite;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @covers \AKlump\CheckPages\Parts\Test
 */
final class TestTest extends TestCase {

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
    $input = $this->getMockBuilder(InputInterface::class)
      ->getMock();
    $output = $this->getMockBuilder(OutputInterface::class)
      ->getMock();

    $runner = new Runner($input, $output);
    $runner->setConfig(['variables' => ['alpha' => 123, 'bravo' => 456]]);

    $suite = new Suite('', [], $runner);

    return new Test($id, $config, $suite);
  }
}
