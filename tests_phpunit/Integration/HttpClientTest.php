<?php

namespace AKlump\CheckPages\Tests\Unit;

use AKlump\CheckPages\Browser\RequestDriverInterface;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\HttpClient;
use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\Parts\Test;
use AKlump\Messaging\HasMessagesInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @group default
 * @covers \AKlump\CheckPages\HttpClient
 * @uses   \AKlump\CheckPages\Parts\Runner
 * @uses   \AKlump\CheckPages\Parts\Suite
 * @uses   \AKlump\CheckPages\Parts\Test
 * @uses   \AKlump\CheckPages\Event\DriverEvent
 * @uses   \AKlump\CheckPages\Output\HttpMessageLogger
 * @uses   \AKlump\CheckPages\Browser\RequestDriver
 * @uses   \AKlump\CheckPages\Helpers\NormalizeHeaders
 * @uses   \AKlump\CheckPages\DataStructure\ContentTypeHeader
 * @uses   \AKlump\CheckPages\DataStructure\HttpHeader
 * @uses   \AKlump\CheckPages\DataStructure\MediaTypeHeader
 * @uses   \AKlump\CheckPages\Files\HttpContentTypeGuesser
 * @uses   \AKlump\CheckPages\Output\Message\Message
 * @uses   \AKlump\CheckPages\Output\VerboseDirective
 */
final class HttpClientTest extends TestCase {

  private HttpClient $client;

  private Runner $runner;

  private $messageBag;

  private $driver;

  private $dispatcher;

  public function testDispatchEventWorksAsExpected() {
    $this->driver->method('setMethod')->willReturn($this->driver);
    $this->driver->method('setBody')->willReturn($this->driver);
    $suite = new Suite('foo', $this->runner);
    $test = new Test('bar', ['why' => 'baz'], $suite);
    $this->client->dispatchEventsWith($test);

    $dispatchedEvents = [];
    $listener = function ($event, $name) use (&$dispatchedEvents) {
      $dispatchedEvents[] = $name;
    };
    $this->dispatcher->addListener(Event::REQUEST_CREATED, $listener);
    $this->dispatcher->addListener(Event::REQUEST_PREPARED, $listener);
    $this->dispatcher->addListener(Event::REQUEST_FINISHED, $listener);

    $request = new Request('GET', 'http://example.com');
    $this->client->sendRequest($request);

    $this->assertContains(Event::REQUEST_CREATED, $dispatchedEvents);
    $this->assertContains(Event::REQUEST_PREPARED, $dispatchedEvents);
    $this->assertContains(Event::REQUEST_FINISHED, $dispatchedEvents);
  }

  public function testSetWhyForNextRequestOnlyWorks() {
    $this->driver->method('setMethod')->willReturn($this->driver);
    $this->driver->method('setBody')->willReturn($this->driver);
    $suite = new Suite('foo', $this->runner);
    $test = new Test('bar', ['why' => 'original why'], $suite);
    $this->client->dispatchEventsWith($test);

    $this->client->setWhyForNextRequestOnly('temporary why');

    $capturedWhys = [];
    $this->dispatcher->addListener(Event::REQUEST_CREATED, function ($event) use (&$capturedWhys) {
      $capturedWhys[] = $event->getTest()->get('why');
    });
    $this->dispatcher->addListener(Event::REQUEST_FINISHED, function ($event) use (&$capturedWhys) {
      $capturedWhys[] = $event->getTest()->get('why');
    });

    $request = new Request('GET', 'http://example.com');
    $this->client->sendRequest($request);

    $this->assertContains('temporary why', $capturedWhys);
  }

  public function testGetDriverReturnsNullInitially() {
    $this->assertNull($this->client->getDriver());
  }

  public function testGetDriverReturnsDriverAfterRequest() {
    $this->driver->method('setMethod')->willReturn($this->driver);
    $this->driver->method('setBody')->willReturn($this->driver);
    $request = new Request('GET', 'http://example.com');
    $this->client->sendRequest($request);
    $this->assertSame($this->driver, $this->client->getDriver());
  }

  public function testSendRequestUsesCorrectMethodAndUri() {
    $this->driver->method('setBody')->willReturn($this->driver);
    $this->driver->expects($this->once())->method('setMethod')->with('POST')->willReturn($this->driver);
    $this->driver->expects($this->once())->method('setUrl')->with('http://example.com/api');

    $request = new Request('POST', 'http://example.com/api');
    $this->client->sendRequest($request);
  }

  public function testSendRequestPassesHeadersToDriver() {
    $this->driver->method('setMethod')->willReturn($this->driver);
    $this->driver->method('setBody')->willReturn($this->driver);
    $this->driver->expects($this->atLeastOnce())
      ->method('setHeader')
      ->withConsecutive(
        ['Host', ['example.com']],
        ['X-Foo', ['bar']],
        ['X-Baz', ['qux']]
      );

    $request = new Request('GET', 'http://example.com', [
      'X-Foo' => 'bar',
      'X-Baz' => 'qux',
    ]);
    $this->client->sendRequest($request);
  }

  protected function setUp(): void {
    $this->runner = $this->getMockBuilder(Runner::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->runner->method('getInput')->willReturn($this->createMock(InputInterface::class));
    $this->runner->method('getOutput')->willReturn($this->createMock(OutputInterface::class));
    $this->runner->method('getDispatcher')->will($this->returnCallback(function() {
        return $this->dispatcher;
    }));
    $this->runner->method('withBaseUrl')->willReturnArgument(0);

    $this->messageBag = $this->createMock(HasMessagesInterface::class);

    $this->driver = $this->createMock(RequestDriverInterface::class);
    $this->driver->method('request')->willReturnSelf();
    $this->driver->method('getResponse')->willReturn(new Response(200, [], Utils::streamFor('OK')));
    $this->driver->method('getUri')->willReturn(new \GuzzleHttp\Psr7\Uri('http://example.com'));

    $this->dispatcher = new EventDispatcher();

    $this->client = new class($this->runner, $this->messageBag, $this->driver) extends HttpClient {

      private $mockDriver;

      public function __construct($runner, $messageBag, $driver) {
        parent::__construct($runner, $messageBag);
        $this->mockDriver = $driver;
      }

      protected function createDriver(): RequestDriverInterface {
        return $this->mockDriver;
      }
    };
  }
}
