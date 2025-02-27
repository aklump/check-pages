<?php

namespace AKlump\CheckPages\Tests\Unit;

use AKlump\CheckPages\HttpClient;
use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Parts\Suite;
use AKlump\Messaging\HasMessagesInterface;
use PHPUnit\Framework\TestCase;
use AKlump\CheckPages\Parts\Test;
use Psr\Http\Client\ClientInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @group default
 * @covers \AKlump\CheckPages\Assert
 */
final class HttpClientTest extends TestCase {

  private ClientInterface $client;

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
    $input = $this
      ->getMockBuilder(InputInterface::class)
      ->getMock();
    $output = $this
      ->getMockBuilder(OutputInterface::class)
      ->getMock();
    $runner = new Runner($input, $output);
    $message_bag = $this
      ->getMockBuilder(HasMessagesInterface::class)
      ->getMock();
    $this->client = new HttpClient($runner, $message_bag);
  }
}
