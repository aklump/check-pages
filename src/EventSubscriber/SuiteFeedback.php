<?php

namespace AKlump\CheckPages\EventSubscriber;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\SuiteEventInterface;
use AKlump\CheckPages\Files\GetShortPath;
use AKlump\CheckPages\Messaging\MessageInterface;
use AKlump\CheckPages\Messaging\MessageType;
use AKlump\CheckPages\Messaging\MessengerInterface;
use AKlump\CheckPages\Output\Flags;
use AKlump\CheckPages\Output\Icons;
use AKlump\CheckPages\Output\Message\Divider;
use AKlump\CheckPages\Output\Message\Message;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\TAP\Directive;
use AKlump\CheckPages\TAP\Result;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides suite-level user feedback.
 */
final class SuiteFeedback implements EventSubscriberInterface {

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents(): array {
    $instance = new self();

    return [
      Event::SUITE_STARTED => [$instance, 'onSuiteStarted'],
      Event::SUITE_FAILED => [$instance, 'onSuiteFailed'],
      Event::SUITE_FINISHED => [$instance, 'onSuiteFinished'],
      Event::SUITE_SKIPPED => [$instance, 'onSuiteSkipped'],
      Event::SUITE_IGNORED => [$instance, 'onSuiteIgnored'],
      //      Event::SUITE_PASSED => [$instance, 'onSuitePassed'],
    ];
  }

  public function onSuiteStarted(SuiteEventInterface $event): void {
    $this->writeSuiteResult($event->getSuite());
  }

  public function onSuiteFinished(SuiteEventInterface $event): void {
    $this->writeSuiteResult($event->getSuite());
  }

  public function onSuiteFailed(SuiteEventInterface $event): void {
    $suite = $event->getSuite();
    $message = (new Message([
      (new GetShortPath())($suite->getFilePath()),
    ], MessageType::ERROR, Verbosity::NORMAL))
      ->setIcon(Icons::FAIL);
    $suite->getRunner()
      ->getMessenger()
      ->writeln($message, Flags::BANNER);
    $suite->getRunner()->getMessenger()->writeln(new Divider());
  }

  public function onSuiteSkipped(SuiteEventInterface $event): void {
    $suite = $event->getSuite();
    $lines = [strval($suite)];
    if ($suite->getRunner()->getOutput()->isVerbose()) {
      $lines[] = '';
    }
    self::echoSuiteTitle($suite->getRunner()
      ->getMessenger(), new Message($lines, MessageType::SKIPPED));
    $suite->getRunner()->getMessenger()->writeln(new Divider());
  }

  public function onSuiteIgnored(SuiteEventInterface $event): void {
    $suite = $event->getSuite();
    $suite->getRunner()->getMessenger()->writeln((new Message(
      [$suite],
      $suite->hasPassed() ? MessageType::SUCCESS : MessageType::INFO,
      Verbosity::NORMAL
    ))->setIcon(Icons::IGNORE));
  }

  public function onSuitePassed(SuiteEventInterface $event): void {
    $suite = $event->getSuite();
    $lines = [strval($suite)];
    self::echoSuiteTitle($suite->getRunner()
      ->getMessenger(), new Message($lines, MessageType::SUCCESS));
    $suite->getRunner()->getMessenger()->writeln(new Divider());
  }

  /**
   * Displays $title in the proper format/color for the suite.
   *
   * @param \AKlump\CheckPages\Messaging\MessengerInterface $messenger
   * @param \AKlump\CheckPages\Messaging\MessageInterface $message
   *   Do not include emojis, they are added for you.
   * @param int|null $flags
   *
   * @return void
   */
  public static function echoSuiteTitle(MessengerInterface $messenger, MessageInterface $message, int $flags = NULL): void {
    switch ($message->getMessageType()) {
      case MessageType::SUCCESS:
        $message->setIcon(Icons::PASS);
        break;

      case MessageType::ERROR:
        $message->setIcon(Icons::FAIL);
        break;

      case MessageType::SKIPPED:
        $message->setIcon(Icons::WARN);
        break;

      default:
        $message->setIcon(Icons::SPYGLASS);
        break;
    }

    $messenger->writeln($message, $flags);
  }

  private function writeSuiteResult(Suite $suite) {
    $directive = $suite->getDirective();
    $icon = match ($directive) {
      Directive::SKIP => Icons::WARN,
      default => NULL,
    };
    $result = $suite->getResult();
    $icon = $icon ?? match ($result) {
      Result::OK => Icons::SUITE_RESULT_OK,
      Result::NOT_OK => Icons::SUITE_RESULT_NOT_OKAY,
      default => Icons::SUITE_RESULT_NONE,
    };
    $icon ??= '';

    $message = trim(sprintf('%s %s',
      $result->value,
      // TODO This should be a number
      $suite->id(),
    ));
    $message .= match ($directive) {
      Directive::SKIP => ' ' . Directive::SKIP->value,
      Directive::TODO => ' ' . Directive::TODO->value,
      default => '',
    };
    $message = (new Message([$message], MessageType::SUITE_RUN_STATE_RESULT))->setIcon($icon);
    $suite->getRunner()->getMessenger()->writeln($message);
  }
}
