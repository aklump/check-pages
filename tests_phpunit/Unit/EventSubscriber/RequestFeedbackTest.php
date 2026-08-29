<?php

namespace AKlump\CheckPages\Tests\Unit\EventSubscriber;

use AKlump\CheckPages\Browser\RequestDriverInterface;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\DriverEvent;
use AKlump\CheckPages\Event\HttpMessageEvent;
use AKlump\CheckPages\EventSubscriber\RequestFeedback;
use AKlump\CheckPages\Messaging\MessageType;
use AKlump\CheckPages\Output\Icons;
use AKlump\CheckPages\Output\Message\Divider;
use AKlump\CheckPages\Output\Message\Message;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\Service\DispatcherFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @group default
 * @covers \AKlump\CheckPages\EventSubscriber\RequestFeedback
 * @uses   \AKlump\CheckPages\Event\DriverEvent
 * @uses   \AKlump\CheckPages\Event\HttpMessageEvent
 * @uses   \AKlump\CheckPages\Messaging\MessageBase
 * @uses   \AKlump\CheckPages\Output\HttpMessageLogger
 * @uses   \AKlump\CheckPages\Output\Message\Divider
 * @uses   \AKlump\CheckPages\Output\Message\Message
 * @uses   \AKlump\CheckPages\Output\VerboseDirective
 * @uses   \AKlump\CheckPages\Parts\Runner
 * @uses   \AKlump\CheckPages\Parts\Suite
 * @uses   \AKlump\CheckPages\Parts\Test
 * @uses   \AKlump\CheckPages\Service\DispatcherFactory
 */
final class RequestFeedbackTest extends TestCase {

  private Runner $runner;

  public function setUp(): void {
    $input = $this->createMock(InputInterface::class);
    $output = $this->createMock(OutputInterface::class);
    $this->runner = new Runner($input, $output);
  }

  public function testGetSubscribedEvents(): void {
    $events = RequestFeedback::getSubscribedEvents();
    $this->assertIsArray($events);
    $this->assertArrayHasKey(Event::REQUEST_STARTED, $events);
    $this->assertArrayHasKey(Event::REQUEST_PREPARED, $events);
    $this->assertArrayHasKey(Event::REQUEST_FINISHED, $events);
    $this->assertArrayHasKey(Event::RESPONSE_RECEIVED, $events);

    $this->assertInstanceOf(RequestFeedback::class, $events[Event::REQUEST_STARTED][0]);
    $this->assertSame('onRequestStarted', $events[Event::REQUEST_STARTED][1]);

    $this->assertInstanceOf(RequestFeedback::class, $events[Event::REQUEST_PREPARED][0]);
    $this->assertSame('onRequestPrepared', $events[Event::REQUEST_PREPARED][1]);

    $this->assertInstanceOf(RequestFeedback::class, $events[Event::REQUEST_FINISHED][0]);
    $this->assertSame('onRequestFinished', $events[Event::REQUEST_FINISHED][1]);

    $this->assertInstanceOf(RequestFeedback::class, $events[Event::RESPONSE_RECEIVED][0]);
    $this->assertSame('onResponseReceived', $events[Event::RESPONSE_RECEIVED][1]);

    $dispatcher = new EventDispatcher();
    DispatcherFactory::addSubscribedEvents($dispatcher, $events);

    $listeners = $dispatcher->getListeners(Event::REQUEST_STARTED);
    $this->assertCount(1, $listeners);
    $this->assertSame($events[Event::REQUEST_STARTED], $listeners[0]);
  }

  public function testOnRequestStartedWithWhy(): void {
    $suite = new Suite('test_suite', $this->runner);
    $test = new Test(1, ['why' => 'Checking homepage', 'url' => '/'], $suite);

    $driver = $this->createMock(RequestDriverInterface::class);
    $event = new DriverEvent($test, $driver);

    $subscriber = new RequestFeedback();
    $subscriber->onRequestStarted($event);

    $messages = $test->getMessages();
    $this->assertCount(1, $messages);
    $this->assertInstanceOf(Message::class, $messages[0]);
    $this->assertSame(MessageType::INFO, $messages[0]->getMessageType());
    $this->assertSame(OutputInterface::VERBOSITY_VERBOSE, $messages[0]->getVerbosity());
    $this->assertSame([Icons::SPYGLASS . 'Checking homepage'], $messages[0]->getMessage());
  }

  public function testOnRequestStartedWithoutWhy(): void {
    $suite = new Suite('test_suite', $this->runner);
    $test = new Test(1, ['url' => '/'], $suite);

    $driver = $this->createMock(RequestDriverInterface::class);
    $event = new DriverEvent($test, $driver);

    $subscriber = new RequestFeedback();
    $subscriber->onRequestStarted($event);

    $messages = $test->getMessages();
    $this->assertCount(0, $messages);
  }

  public function testOnResponseReceivedAddsDivider(): void {
    $suite = new Suite('test_suite', $this->runner);
    $test = new Test(1, ['url' => '/'], $suite);

    $event = new HttpMessageEvent([], 'Response body contents', 200, $test);
    $subscriber = new RequestFeedback();
    $subscriber->onResponseReceived($event);

    $messages = $test->getMessages();
    $this->assertCount(1, $messages);
    $this->assertInstanceOf(Divider::class, $messages[0]);
  }

  public function testOnRequestFinishedLogsResponseForPassedTest(): void {
    $suite = new Suite('test_suite', $this->runner);
    $test = new Test(1, ['url' => '/'], $suite);

    $response = $this->createMock(ResponseInterface::class);
    $driver = $this->createMock(RequestDriverInterface::class);
    $driver->method('getResponse')->willReturn($response);

    $event = new DriverEvent($test, $driver);
    $subscriber = new RequestFeedback();
    $subscriber->onRequestFinished($event);

    $this->assertTrue(true);
  }

  public function testOnRequestFinishedLogsResponseForFailedTest(): void {
    $suite = new Suite('test_suite', $this->runner);
    $test = new Test(1, ['url' => '/'], $suite);
    $test->setFailed();

    $response = $this->createMock(ResponseInterface::class);
    $driver = $this->createMock(RequestDriverInterface::class);
    $driver->method('getResponse')->willReturn($response);

    $event = new DriverEvent($test, $driver);
    $subscriber = new RequestFeedback();
    $subscriber->onRequestFinished($event);

    $this->assertTrue(true);
  }

}
