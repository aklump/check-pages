<?php

namespace AKlump\CheckPages\Tests\Unit\EventSubscriber;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\RunnerEvent;
use AKlump\CheckPages\EventSubscriber\RunnerFeedback;
use AKlump\CheckPages\Messaging\MessengerInterface;
use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Service\DispatcherFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @group default
 * @covers \AKlump\CheckPages\EventSubscriber\RunnerFeedback
 * @uses   \AKlump\CheckPages\Event\RunnerEvent
 * @uses   \AKlump\CheckPages\Parts\Runner
 * @uses   \AKlump\CheckPages\Service\DispatcherFactory
 */
final class RunnerFeedbackTest extends TestCase {

  public function testGetSubscribedEvents(): void {
    $events = RunnerFeedback::getSubscribedEvents();
    $this->assertIsArray($events);
    $this->assertArrayHasKey(Event::RUNNER_CREATED, $events);
    $this->assertInstanceOf(RunnerFeedback::class, $events[Event::RUNNER_CREATED][0][0]);
    $this->assertSame('onRunnerCreated', $events[Event::RUNNER_CREATED][0][1]);
    $this->assertSame(-100, $events[Event::RUNNER_CREATED][1]);

    $dispatcher = new EventDispatcher();
    DispatcherFactory::addSubscribedEvents($dispatcher, $events);

    $listeners = $dispatcher->getListeners(Event::RUNNER_CREATED);
    $this->assertCount(1, $listeners);
    $this->assertSame($events[Event::RUNNER_CREATED][0], $listeners[0]);
  }

  public function testOnRunnerCreatedSetsTitleFromBaseUrl(): void {
    $input = $this->createMock(InputInterface::class);
    $output = $this->createMock(OutputInterface::class);
    $output->method('isDebug')->willReturn(FALSE);
    $output->method('isVerbose')->willReturn(FALSE);
    $output->method('isVeryVerbose')->willReturn(FALSE);
    $output->method('isQuiet')->willReturn(FALSE);

    $messenger = $this->createMock(MessengerInterface::class);

    $runner = $this->getMockBuilder(Runner::class)
      ->setConstructorArgs([$input, $output])
      ->onlyMethods(['getPathToRunnerFile', 'getMessenger', 'getFilters', 'getOutput', 'get'])
      ->getMock();

    $runner->method('getPathToRunnerFile')
      ->willReturn('/path/to/runner.php');

    $runner->method('getMessenger')
      ->willReturn($messenger);

    $runner->method('getFilters')
      ->willReturn([]);

    $runner->method('getOutput')
      ->willReturn($output);

    $runner->method('get')
      ->with('base_url')
      ->willReturn('https://example.com');

    $event = new RunnerEvent($runner);
    $subscriber = new RunnerFeedback();
    $subscriber->onRunnerCreated($event);

    $this->assertTrue(TRUE);
  }

}
