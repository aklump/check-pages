<?php

namespace AKlump\CheckPages\Tests\Unit\EventSubscriber;

use AKlump\CheckPages\Assert;
use AKlump\CheckPages\Browser\RequestDriverInterface;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\AssertEvent;
use AKlump\CheckPages\EventSubscriber\AssertionFeedback;
use AKlump\CheckPages\Messaging\MessageType;
use AKlump\CheckPages\Output\Icons;
use AKlump\CheckPages\Output\Message\Message;
use AKlump\CheckPages\Output\Message\NewLine;
use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\Service\DispatcherFactory;
use AKlump\CheckPages\View\DefaultTheme;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @group default
 * @covers \AKlump\CheckPages\EventSubscriber\AssertionFeedback
 * @uses   \AKlump\CheckPages\Assert
 * @uses   \AKlump\CheckPages\Event\AssertEvent
 * @uses   \AKlump\CheckPages\Output\Message\Message
 * @uses   \AKlump\CheckPages\Output\Message\NewLine
 * @uses   \AKlump\CheckPages\Output\VerboseDirective
 * @uses   \AKlump\CheckPages\Messaging\MessageBase
 * @uses   \AKlump\CheckPages\Parts\Runner
 * @uses   \AKlump\CheckPages\Parts\Suite
 * @uses   \AKlump\CheckPages\Parts\Test
 * @uses   \AKlump\CheckPages\Variables
 * @uses   \AKlump\CheckPages\View\DefaultTheme
 */
final class AssertionFeedbackTest extends TestCase {

  private Runner $runner;

  public function setUp(): void {
    $input = $this->createMock(InputInterface::class);
    $output = $this->createMock(OutputInterface::class);
    $this->runner = new Runner($input, $output);
  }

  private function createTestInstance(array $config = []): Test {
    $suite = new Suite('test_suite', $this->runner);

    return new Test(1, $config, $suite);
  }

  public function testGetSubscribedEvents() {
    $events = AssertionFeedback::getSubscribedEvents();
    $this->assertIsArray($events);
    $this->assertArrayHasKey(Event::ASSERT_PASSED, $events);
    $this->assertArrayHasKey(Event::ASSERT_FAILED, $events);
    $this->assertArrayHasKey(Event::ASSERT_FINISHED, $events);

    $this->assertInstanceOf(AssertionFeedback::class, $events[Event::ASSERT_PASSED][0]);
    $this->assertSame('onAssertionPassed', $events[Event::ASSERT_PASSED][1]);

    $this->assertInstanceOf(AssertionFeedback::class, $events[Event::ASSERT_FAILED][0]);
    $this->assertSame('onAssertionFailed', $events[Event::ASSERT_FAILED][1]);

    $this->assertInstanceOf(AssertionFeedback::class, $events[Event::ASSERT_FINISHED][0]);
    $this->assertSame('onAssertionFinished', $events[Event::ASSERT_FINISHED][1]);

    $dispatcher = new EventDispatcher();
    DispatcherFactory::addSubscribedEvents($dispatcher, $events);

    $listeners = $dispatcher->getListeners(Event::ASSERT_PASSED);
    $this->assertCount(1, $listeners);
    $this->assertSame($events[Event::ASSERT_PASSED], $listeners[0]);
  }

  public function testOnAssertionPassedWithoutCustomWhy() {
    $test = $this->createTestInstance();
    $assert = new Assert('0', ['is' => 200], $test);
    $driver = $this->createMock(RequestDriverInterface::class);
    $event = new AssertEvent($assert, $test, $driver);

    $subscriber = new AssertionFeedback();
    $subscriber->onAssertionPassed($event);

    $messages = $test->getMessages();
    $this->assertCount(1, $messages);
    $message = $messages[0];
    $this->assertInstanceOf(Message::class, $message);
    $this->assertSame(MessageType::PASSED_ASSERTION, $message->getMessageType());
    $this->assertSame(OutputInterface::VERBOSITY_VERBOSE, $message->getVerbosity());
    $this->assertSame([strval($assert)], $message->getMessage());
  }

  public function testOnAssertionPassedWithCustomWhy() {
    $test = $this->createTestInstance();
    $assert = new Assert('0', ['why' => 'Verify status code is OK', 'is' => 200], $test);
    $driver = $this->createMock(RequestDriverInterface::class);
    $event = new AssertEvent($assert, $test, $driver);

    $subscriber = new AssertionFeedback();
    $subscriber->onAssertionPassed($event);

    $messages = $test->getMessages();
    $this->assertCount(1, $messages);
    $message = $messages[0];
    $expectedWhy = 'Verify status code is OK ' . strval($assert);
    $this->assertSame([$expectedWhy], $message->getMessage());
    $this->assertSame(MessageType::PASSED_ASSERTION, $message->getMessageType());
  }

  public function testOnAssertionPassedWithEmptyWhyInConfigDoesNotSuppressMessage() {
    $test = $this->createTestInstance();
    $assert = new Assert('0', ['why' => '', 'is' => 200], $test);
    $driver = $this->createMock(RequestDriverInterface::class);
    $event = new AssertEvent($assert, $test, $driver);

    $subscriber = new AssertionFeedback();
    $subscriber->onAssertionPassed($event);

    $messages = $test->getMessages();
    $this->assertCount(1, $messages);
    $this->assertSame([strval($assert)], $messages[0]->getMessage());
    $this->assertSame(MessageType::PASSED_ASSERTION, $messages[0]->getMessageType());
  }

  public function testOnAssertionFailedWithoutCustomWhy() {
    $test = $this->createTestInstance();
    $assert = new Assert('0', ['is' => 200], $test);
    $driver = $this->createMock(RequestDriverInterface::class);
    $event = new AssertEvent($assert, $test, $driver);

    $subscriber = new AssertionFeedback();
    $subscriber->onAssertionFailed($event);

    $messages = $test->getMessages();
    $this->assertCount(1, $messages);
    $message = $messages[0];
    $this->assertInstanceOf(Message::class, $message);
    $this->assertSame(MessageType::FAILED_ASSERTION, $message->getMessageType());
    $this->assertSame(OutputInterface::VERBOSITY_VERBOSE, $message->getVerbosity());
    $this->assertSame([strval($assert)], $message->getMessage());
  }

  public function testOnAssertionFailedWithCustomWhy() {
    $test = $this->createTestInstance();
    $assert = new Assert('0', ['why' => 'Verify status code is OK', 'is' => 200], $test);
    $driver = $this->createMock(RequestDriverInterface::class);
    $event = new AssertEvent($assert, $test, $driver);

    $subscriber = new AssertionFeedback();
    $subscriber->onAssertionFailed($event);

    $messages = $test->getMessages();
    $this->assertCount(1, $messages);
    $expectedWhy = 'Verify status code is OK ' . strval($assert);
    $this->assertSame([$expectedWhy], $messages[0]->getMessage());
    $this->assertSame(MessageType::FAILED_ASSERTION, $messages[0]->getMessageType());
  }

  public function testOnAssertionFailedWithEmptyWhyInConfig() {
    $test = $this->createTestInstance();
    $assert = new Assert('0', ['why' => '', 'is' => 200], $test);
    $driver = $this->createMock(RequestDriverInterface::class);
    $event = new AssertEvent($assert, $test, $driver);

    $subscriber = new AssertionFeedback();
    $subscriber->onAssertionFailed($event);

    $messages = $test->getMessages();
    $this->assertCount(1, $messages);
    $this->assertSame([strval($assert)], $messages[0]->getMessage());
  }

  public function testOnAssertionFailedWithReason() {
    $test = $this->createTestInstance();
    $assert = new Assert('0', ['is' => 200], $test);
    $assert->setReason('Response code was 404 Not Found');
    $driver = $this->createMock(RequestDriverInterface::class);
    $event = new AssertEvent($assert, $test, $driver);

    $subscriber = new AssertionFeedback();
    $subscriber->onAssertionFailed($event);

    $messages = $test->getMessages();
    $this->assertCount(2, $messages);

    $failMessage = $messages[0];
    $this->assertSame(MessageType::FAILED_ASSERTION, $failMessage->getMessageType());
    $this->assertSame([strval($assert)], $failMessage->getMessage());

    $reasonMessage = $messages[1];
    $this->assertSame(MessageType::INFO, $reasonMessage->getMessageType());
    $this->assertSame(['Response code was 404 Not Found'], $reasonMessage->getMessage());
    $this->assertSame(Icons::SHRUG, $reasonMessage->getIcon());
    $this->assertSame(OutputInterface::VERBOSITY_VERBOSE, $reasonMessage->getVerbosity());
  }

  public function testOnAssertionFailedWithoutReasonDoesNotAddSecondMessage() {
    $test = $this->createTestInstance();
    $assert = new Assert('0', ['is' => 200], $test);
    $driver = $this->createMock(RequestDriverInterface::class);
    $event = new AssertEvent($assert, $test, $driver);

    $subscriber = new AssertionFeedback();
    $subscriber->onAssertionFailed($event);

    $messages = $test->getMessages();
    $this->assertCount(1, $messages);
    $this->assertSame(MessageType::FAILED_ASSERTION, $messages[0]->getMessageType());
  }

  public function testOnAssertionFinished() {
    $test = $this->createTestInstance();
    $assert = new Assert('0', ['is' => 200], $test);
    $driver = $this->createMock(RequestDriverInterface::class);
    $event = new AssertEvent($assert, $test, $driver);

    $subscriber = new AssertionFeedback();
    $subscriber->onAssertionFinished($event);

    $messages = $test->getMessages();
    $this->assertCount(1, $messages);
    $this->assertInstanceOf(NewLine::class, $messages[0]);
    $this->assertSame(OutputInterface::VERBOSITY_VERBOSE, $messages[0]->getVerbosity());
  }

  public function testDefaultThemeDoesNotOverwriteCustomIcons() {
    $theme = new DefaultTheme();

    // Default icon assignment when none is set
    $passedWithoutIcon = new Message(['Success'], MessageType::PASSED_ASSERTION);
    $theme->passedAssertion($passedWithoutIcon);
    $this->assertSame(Icons::COMPLETE, $passedWithoutIcon->getIcon());

    $failedWithoutIcon = new Message(['Failed'], MessageType::FAILED_ASSERTION);
    $theme->failedAssertion($failedWithoutIcon);
    $this->assertSame(Icons::INCOMPLETE, $failedWithoutIcon->getIcon());

    // Custom icon should be preserved
    $passedWithCustomIcon = (new Message(['Custom Pass'], MessageType::PASSED_ASSERTION))->setIcon('⭐');
    $theme->passedAssertion($passedWithCustomIcon);
    $this->assertSame('⭐', $passedWithCustomIcon->getIcon());

    $failedWithCustomIcon = (new Message(['Custom Fail'], MessageType::FAILED_ASSERTION))->setIcon(Icons::SHRUG);
    $theme->failedAssertion($failedWithCustomIcon);
    $this->assertSame(Icons::SHRUG, $failedWithCustomIcon->getIcon());
  }

}
