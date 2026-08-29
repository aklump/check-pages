<?php

namespace AKlump\CheckPages\EventSubscriber;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Output\Icons;
use AKlump\CheckPages\Output\Message\Message;
use AKlump\CheckPages\Output\Message\NewLine;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\CheckPages\Messaging\MessageType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use AKlump\CheckPages\Event\AssertEventInterface;

/**
 * Provides user feedback based on output verbosity.
 */
final class AssertionFeedback implements EventSubscriberInterface {

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents(): array {
    $instance = new self();

    return [
      Event::ASSERT_PASSED => [$instance, 'onAssertionPassed'],
      Event::ASSERT_FAILED => [$instance, 'onAssertionFailed'],
      Event::ASSERT_FINISHED => [$instance, 'onAssertionFinished'],
    ];
  }

  public function onAssertionPassed(AssertEventInterface $event): void {
    $assert = $event->getAssert();
    $why = $this->getWhy($assert);
    if (!$why) {
      return;
    }
    $event->getTest()->addMessage((new Message(
      [$why],
      MessageType::PASSED_ASSERTION,
      Verbosity::VERBOSE
    )));
  }

  public function onAssertionFailed(AssertEventInterface $event): void {
    $test = $event->getTest();
    $assert = $event->getAssert();
    $why = $this->getWhy($assert);
    $test->addMessage((new Message(
      [$why],
      MessageType::FAILED_ASSERTION,
      Verbosity::VERBOSE
    )));
    $reason = $assert->getReason();
    if (!$reason) {
      return;
    }
    $test->addMessage((new Message(
      [$reason],
      MessageType::INFO,
      Verbosity::VERBOSE
    ))->setIcon(Icons::SHRUG)
    );
  }

  public function onAssertionFinished(AssertEventInterface $event): void {
    $test = $event->getTest();
    $test->addMessage(new NewLine(Verbosity::VERBOSE, 2));
  }

  private function getWhy($assert): string {
    $why = strval($assert);
    $config = $assert->getConfig();
    if (empty($config['why'])) {
      return $why;
    }

    return "{$config['why']} $why";
  }

}
