<?php

namespace AKlump\CheckPages\Tests\Unit\EventSubscriber;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\TestEvent;
use AKlump\CheckPages\EventSubscriber\TestFeedback;
use AKlump\CheckPages\Messaging\MessageType;
use AKlump\CheckPages\Output\Message\Message;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\Service\DispatcherFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @group default
 * @covers \AKlump\CheckPages\EventSubscriber\TestFeedback
 * @uses   \AKlump\CheckPages\Event\TestEvent
 * @uses   \AKlump\CheckPages\Messaging\MessageBase
 * @uses   \AKlump\CheckPages\Output\Message\Message
 * @uses   \AKlump\CheckPages\Output\VerboseDirective
 * @uses   \AKlump\CheckPages\Parts\Runner
 * @uses   \AKlump\CheckPages\Parts\Suite
 * @uses   \AKlump\CheckPages\Parts\Test
 * @uses   \AKlump\CheckPages\Service\DispatcherFactory
 */
final class TestFeedbackTest extends TestCase {

  private Runner $runner;

  public function setUp(): void {
    $input = $this->createMock(InputInterface::class);
    $output = $this->createMock(OutputInterface::class);
    $this->runner = new Runner($input, $output);
  }

  public function testGetSubscribedEvents(): void {
    $events = TestFeedback::getSubscribedEvents();
    $this->assertIsArray($events);
    $this->assertArrayHasKey(Event::TEST_FAILED, $events);
    $this->assertArrayHasKey(Event::TEST_FINISHED, $events);

    $this->assertInstanceOf(TestFeedback::class, $events[Event::TEST_FAILED][0]);
    $this->assertSame('onTestFailed', $events[Event::TEST_FAILED][1]);

    $this->assertInstanceOf(TestFeedback::class, $events[Event::TEST_FINISHED][0]);
    $this->assertSame('onTestFinished', $events[Event::TEST_FINISHED][1]);

    $dispatcher = new EventDispatcher();
    DispatcherFactory::addSubscribedEvents($dispatcher, $events);

    $listeners = $dispatcher->getListeners(Event::TEST_FAILED);
    $this->assertCount(1, $listeners);
    $this->assertSame($events[Event::TEST_FAILED], $listeners[0]);
  }

  public function testOnTestFailedWithoutWhy(): void {
    $suite = new Suite('test_suite', $this->runner);
    $test = new Test(1, ['url' => '/test'], $suite);

    $event = new TestEvent($test);
    $subscriber = new TestFeedback();
    $subscriber->onTestFailed($event);

    $messages = $test->getMessages();
    $this->assertCount(1, $messages);
    $message = $messages[0];
    $this->assertInstanceOf(Message::class, $message);
    $this->assertSame(MessageType::ERROR, $message->getMessageType());
    $this->assertSame(OutputInterface::VERBOSITY_VERBOSE, $message->getVerbosity());
    $this->assertSame([strval($test)], $message->getMessage());
  }

  public function testOnTestFailedWithWhy(): void {
    $suite = new Suite('test_suite', $this->runner);
    $test = new Test(1, ['why' => 'Check login page', 'url' => '/login'], $suite);

    $event = new TestEvent($test);
    $subscriber = new TestFeedback();
    $subscriber->onTestFailed($event);

    $messages = $test->getMessages();
    $this->assertCount(1, $messages);
    $message = $messages[0];
    $this->assertSame(MessageType::ERROR, $message->getMessageType());
    $this->assertSame([strval($test) . ': Check login page'], $message->getMessage());
  }

  public function testOnTestFinishedRunsWithoutErrors(): void {
    $suite = new Suite('test_suite', $this->runner);
    $test = new Test(1, ['url' => '/test'], $suite);

    $event = new TestEvent($test);
    $subscriber = new TestFeedback();
    $subscriber->onTestFinished($event);

    $this->assertCount(0, $test->getMessages());
  }

}
