<?php

namespace AKlump\CheckPages\EventSubscriber;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\RunnerEvent;
use AKlump\CheckPages\Files\GetShortPath;
use AKlump\CheckPages\Messaging\MessageType;
use AKlump\CheckPages\Output\Flags;
use AKlump\CheckPages\Output\Message\Message;
use AKlump\CheckPages\Output\Verbosity;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides runner-level user feedback.
 */
final class RunnerFeedback implements EventSubscriberInterface {

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents(): array {
    $instance = new self();

    return [
      Event::RUNNER_CREATED => [
        [$instance, 'onRunnerCreated'],
        -100,
      ],
      Event::RUNNER_FINISHED => [
        [$instance, 'onRunnerFinished'],
        -100,
      ],
    ];
  }

  public function onRunnerCreated(RunnerEvent $event): void {
    $runner = $event->getRunner();
    $messenger = $runner->getMessenger();

    $lines = [];
    $path = (new GetShortPath())($runner->getPathToRunnerFile());
    $lines[] = sprintf('Testing started with %s', $path);

    /**
     * Filters
     */
    $filters = $runner->getFilters();
    if (count($filters)) {
      $lines[] = sprintf('Filters: %s', implode(', ', $filters));
    }


    if ($runner->getOutput()->isDebug()) {
      $lines[] = sprintf('Start: %s', $runner->getTimer()->getCurrent());
    }
    $messenger->writeln(new Message(
      $lines,
      MessageType::INFO,
      Verbosity::VERY_VERBOSE
    ));

    /**
     * Base URL
     */
    $messenger->writeln(
      new Message([$runner->get('base_url')], MessageType::TITLE),
    );
  }

  public function onRunnerFinished(RunnerEvent $event): void {
    $runner = $event->getRunner();
    $messenger = $runner->getMessenger();

    foreach ($runner->getMessages() as $message) {
      $messenger->writeln($message);
    }

    $total_test_count = $runner->getTotalTestsRun();

    $lines = [];
    // Percentage
    $percentage = NULL;
    if ($runner->hasPassed()) {
      $passed_test_count = $runner->getTotalPassingTestsRun();
      $percentage = $total_test_count ? intval(100 * $passed_test_count / $total_test_count) : 100;
      $lines[] = sprintf("%d / %d (%d%%)", $passed_test_count, $total_test_count, $percentage);
    }

    $lines[] = sprintf("Time: %s", $runner->getTimer()->getElapsed());
    if ($runner->getOutput()->isDebug()) {
      $lines[] = sprintf('End: %s', $runner->getTimer()->getCurrent());
    }
    $messenger->writeln(new Message($lines, MessageType::INFO, Verbosity::VERBOSE));

    if ($runner->getTotalSkippedSuites() > 0) {
      $messenger->writeln(new Message(['At least one suite was skipped.'], MessageType::WARNING));
    }

    $total_assertion_count = $runner->getTotalAssertionsRun();
    $ok = $runner->hasPassed() || 100 === $percentage;
    $flags = Flags::INVERT_FIRST_LINE;
    if ($total_test_count > 0 && $ok) {
      $message = new Message([
        sprintf("OK (%d test%s, %d assertion%s)",
          $total_test_count,
          $total_test_count === 1 ? '' : 's',
          $total_assertion_count,
          $total_assertion_count === 1 ? '' : 's'
        ),
      ],
        MessageType::SUCCESS
      );
    }
    else {
      // TODO Should this be removed because too much noise?
      $last_failed_suite = $runner->getLastFailedSuite();
      if ($last_failed_suite) {
        $message = new Message([
          sprintf("Failed suite: %s", (new GetShortPath())($last_failed_suite->getFilePath())),
        ], MessageType::ERROR);
        $messenger->writeln($message, Flags::BANNER);
      }

      // Sometimes a test fails without an assertion failing, e.g. the HTTP response code.
      $failed_count = max($runner->getTotalFailedTests(), $runner->getTotalFailedAssertions(), intval($runner->hasFailed()));
      $failed_count = min($failed_count, $total_test_count);
      $lines = [
        sprintf('Tests: %d, Assertions: %d, Failures: %d',
          $total_test_count,
          $total_assertion_count,
          $failed_count
        ),
      ];

      if ($failed_count > 0 && $total_test_count > 0) {
        array_unshift($lines, 'FAILURES!');
      }
      else {
        $flags = NULL;
      }

      $message_type = $total_test_count > 0 ? MessageType::ERROR : MessageType::INFO;
      $message = new Message($lines, $message_type);
    }
    $messenger->writeln($message, $flags);
  }

}
