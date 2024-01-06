<?php

namespace AKlump\CheckPages\Tests\Unit;

use AKlump\CheckPages\Parts\Suite;
use PHPUnit\Framework\TestCase;
use AKlump\CheckPages\Parts\Test;

/**
 * @group default
 * @covers \AKlump\CheckPages\Assert
 */
final class HttpClientTest extends TestCase {

  public function testDispatchEventWorksAsExpected() {
    $suite = new Suite('', $this->client->getRunner());
    $test = new Test('', [], $suite);
    $result = $this->client->dispatchEventsWith($test);
    $this->assertSame($this->client, $result);
  }

  public function testSetWhyForNextRequestWorksWithoutDispatcherTest() {
    $result = $this->client->setWhyForNextRequestOnly('Lorem ipsum');
    $this->assertSame($this->client, $result);
  }

  public function setUp(): void {
    $input = $this->getMockBuilder(\Symfony\Component\Console\Input\InputInterface::class)
      ->getMock();
    $output = $this->getMockBuilder(\Symfony\Component\Console\Output\OutputInterface::class)
      ->getMock();

    $this->runner = new \AKlump\CheckPages\Parts\Runner($input, $output);
    $runner = new \AKlump\CheckPages\Parts\Runner($input, $output);

    $message_bag = $this->getMockBuilder(\AKlump\Messaging\HasMessagesInterface::class)
      ->getMock();
    $this->client = new \AKlump\CheckPages\HttpClient($runner, $message_bag);
  }
}
