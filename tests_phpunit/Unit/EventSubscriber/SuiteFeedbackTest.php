<?php

namespace AKlump\CheckPages\Tests\Unit\EventSubscriber;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\SuiteEvent;
use AKlump\CheckPages\EventSubscriber\SuiteFeedback;
use AKlump\CheckPages\Messaging\MessageInterface;
use AKlump\CheckPages\Messaging\MessageType;
use AKlump\CheckPages\Messaging\MessengerInterface;
use AKlump\CheckPages\Output\Flags;
use AKlump\CheckPages\Output\Icons;
use AKlump\CheckPages\Output\Message\Divider;
use AKlump\CheckPages\Output\Message\Message;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\Service\DispatcherFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @group default
 * @covers \AKlump\CheckPages\EventSubscriber\SuiteFeedback
 * @uses   \AKlump\CheckPages\Event\SuiteEvent
 * @uses   \AKlump\CheckPages\Files\GetShortPath
 * @uses   \AKlump\CheckPages\Messaging\MessageBase
 * @uses   \AKlump\CheckPages\Output\Message\Divider
 * @uses   \AKlump\CheckPages\Output\Message\Message
 * @uses   \AKlump\CheckPages\Output\VerboseDirective
 * @uses   \AKlump\CheckPages\Parts\Runner
 * @uses   \AKlump\CheckPages\Parts\Suite
 * @uses   \AKlump\CheckPages\Service\DispatcherFactory
 */
final class SuiteFeedbackTest extends TestCase {

  private Runner $runner;

  private MessengerInterface $messenger;

  public function setUp(): void {
    $input = $this->createMock(InputInterface::class);
    $output = $this->createMock(OutputInterface::class);
    $this->messenger = $this->createMock(MessengerInterface::class);

    $this->runner = $this->getMockBuilder(Runner::class)
      ->setConstructorArgs([$input, $output])
      ->onlyMethods(['getMessenger', 'getOutput'])
      ->getMock();

    $this->runner->method('getMessenger')
      ->willReturn($this->messenger);
    $this->runner->method('getOutput')
      ->willReturn($output);
  }

  public function testGetSubscribedEvents(): void {
    $events = SuiteFeedback::getSubscribedEvents();
    $this->assertIsArray($events);
    $this->assertArrayHasKey(Event::SUITE_CREATED, $events);
    $this->assertArrayHasKey(Event::SUITE_FAILED, $events);
    $this->assertArrayHasKey(Event::SUITE_SKIPPED, $events);
    $this->assertArrayHasKey(Event::SUITE_PASSED, $events);

    $this->assertInstanceOf(SuiteFeedback::class, $events[Event::SUITE_CREATED][0]);
    $this->assertSame('onSuiteCreated', $events[Event::SUITE_CREATED][1]);

    $this->assertInstanceOf(SuiteFeedback::class, $events[Event::SUITE_FAILED][0]);
    $this->assertSame('onSuiteFailed', $events[Event::SUITE_FAILED][1]);

    $this->assertInstanceOf(SuiteFeedback::class, $events[Event::SUITE_SKIPPED][0]);
    $this->assertSame('onSuiteSkipped', $events[Event::SUITE_SKIPPED][1]);

    $this->assertInstanceOf(SuiteFeedback::class, $events[Event::SUITE_PASSED][0]);
    $this->assertSame('onSuitePassed', $events[Event::SUITE_PASSED][1]);

    $dispatcher = new EventDispatcher();
    DispatcherFactory::addSubscribedEvents($dispatcher, $events);

    $listeners = $dispatcher->getListeners(Event::SUITE_CREATED);
    $this->assertCount(1, $listeners);
    $this->assertSame($events[Event::SUITE_CREATED], $listeners[0]);
  }

  public function testOnSuiteCreatedDeliversBannerMessage(): void {
    $suite = new Suite('my_suite', $this->runner);
    $suite->setFilePath('/path/to/suite.yml');

    $this->messenger->expects($this->once())
      ->method('writeln')
      ->with(
        $this->callback(function (MessageInterface $msg) {
          return $msg->getMessageType() === MessageType::BANNER
            && $msg->getVerbosity() === OutputInterface::VERBOSITY_VERBOSE
            && str_contains($msg->getMessage()[0], 'MY_SUITE');
        }),
      );

    $event = new SuiteEvent($suite);
    $subscriber = new SuiteFeedback();
    $subscriber->onSuiteCreated($event);
  }

  public function testOnSuiteFailedDeliversBannerAndDivider(): void {
    $suite = new Suite('failed_suite', $this->runner);
    $suite->setFilePath('/path/to/failed.yml');

    $delivered = [];
    $this->messenger->expects($this->exactly(2))
      ->method('writeln')
      ->willReturnCallback(function (MessageInterface $msg, $flags = NULL) use (&$delivered) {
        $delivered[] = [$msg, $flags];
      });

    $event = new SuiteEvent($suite);
    $subscriber = new SuiteFeedback();
    $subscriber->onSuiteFailed($event);

    $this->assertCount(2, $delivered);
    $this->assertInstanceOf(Message::class, $delivered[0][0]);
    $this->assertSame(MessageType::ERROR, $delivered[0][0]->getMessageType());
    $this->assertSame(Icons::FAIL, $delivered[0][0]->getIcon());
    $this->assertSame(Flags::BANNER, $delivered[0][1]);
    $this->assertInstanceOf(Divider::class, $delivered[1][0]);
  }

  public function testOnSuiteSkippedDeliversTitleAndDivider(): void {
    $suite = new Suite('skipped_suite', $this->runner);

    $delivered = [];
    $this->messenger->expects($this->exactly(2))
      ->method('writeln')
      ->willReturnCallback(function (MessageInterface $msg, $flags = NULL) use (&$delivered) {
        $delivered[] = [$msg, $flags];
      });

    $event = new SuiteEvent($suite);
    $subscriber = new SuiteFeedback();
    $subscriber->onSuiteSkipped($event);

    $this->assertCount(2, $delivered);
    $this->assertSame(MessageType::SKIPPED, $delivered[0][0]->getMessageType());
    $this->assertSame(Icons::WARN, $delivered[0][0]->getIcon());
    $this->assertInstanceOf(Divider::class, $delivered[1][0]);
  }

  public function testOnSuitePassedDeliversTitleAndDivider(): void {
    $suite = new Suite('passed_suite', $this->runner);

    $delivered = [];
    $this->messenger->expects($this->exactly(2))
      ->method('writeln')
      ->willReturnCallback(function (MessageInterface $msg, $flags = NULL) use (&$delivered) {
        $delivered[] = [$msg, $flags];
      });

    $event = new SuiteEvent($suite);
    $subscriber = new SuiteFeedback();
    $subscriber->onSuitePassed($event);

    $this->assertCount(2, $delivered);
    $this->assertSame(MessageType::SUCCESS, $delivered[0][0]->getMessageType());
    $this->assertSame(Icons::PASS, $delivered[0][0]->getIcon());
    $this->assertInstanceOf(Divider::class, $delivered[1][0]);
  }

  public function testEchoSuiteTitleSetsCorrectIcons(): void {
    $messenger = $this->createMock(MessengerInterface::class);

    $successMsg = new Message(['Success'], MessageType::SUCCESS);
    SuiteFeedback::echoSuiteTitle($messenger, $successMsg);
    $this->assertSame(Icons::PASS, $successMsg->getIcon());

    $errorMsg = new Message(['Error'], MessageType::ERROR);
    SuiteFeedback::echoSuiteTitle($messenger, $errorMsg);
    $this->assertSame(Icons::FAIL, $errorMsg->getIcon());

    $skippedMsg = new Message(['Skipped'], MessageType::SKIPPED);
    SuiteFeedback::echoSuiteTitle($messenger, $skippedMsg);
    $this->assertSame(Icons::WARN, $skippedMsg->getIcon());

    $defaultMsg = new Message(['Default'], MessageType::INFO);
    SuiteFeedback::echoSuiteTitle($messenger, $defaultMsg);
    $this->assertSame(Icons::SPYGLASS, $defaultMsg->getIcon());
  }

}
