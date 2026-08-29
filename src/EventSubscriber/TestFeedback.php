<?php

namespace AKlump\CheckPages\EventSubscriber;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Files\GetShortPath;
use AKlump\CheckPages\Messaging\MessageType;
use AKlump\CheckPages\Output\Icons;
use AKlump\CheckPages\Output\Message\Message;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\Service\SystemResourcesManager;
use AKlump\CheckPages\TAP\Directive;
use AKlump\CheckPages\TAP\Result;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use AKlump\CheckPages\Event\TestRunstateEvent;

/**
 * Provides test-level user feedback.
 */
final class TestFeedback implements EventSubscriberInterface {

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents(): array {
    $instance = new self();

    return [
      Event::TEST_STARTED => [$instance, 'onTestStarted'],
      Event::TEST_RUNSTATE_CHANGED => [$instance, 'onTestRunstateChanged'],
      Event::TEST_FAILED => [$instance, 'onTestFailed'],
      Event::TEST_FINISHED => [$instance, 'onTestFinished'],
    ];
  }

  public function onTestStarted(TestEventInterface $event): void {
    $this->writeTestResult($event->getTest());

    /**
     * Information about the resource usage.
     */
    $lines = [];
    $resources = new SystemResourcesManager();
    $open_files_usage = $resources->getOpenFilesPercentage();
    $type = MessageType::INFO;
    $verbosity = Verbosity::DEBUG;
    if ($open_files_usage > 80) {
      $type = MessageType::ERROR;
      $verbosity = Verbosity::NORMAL;
    }
    $lines[] = sprintf('Open files: %d%% of %d', round($open_files_usage, 2), $resources->getSystemLimit());
    $memory_used = round(memory_get_usage() / 1024 / 1024, 2) . 'MB';
    $lines[] = 'Memory used: ' . $memory_used;
    $event->getTest()->addMessage(new Message($lines, $type, $verbosity));
    unset($lines);
  }

  public function onTestRunstateChanged(TestRunstateEvent $event) {
    $diff = $event->diff();
    if (empty($diff)) {
      return;
    }

    $state = $event->getTestRunState();
    $test = $state->getTest();

    /**
     * File changes
     */
    if (array_key_exists('files', $diff)) {
      $short_paths = array_map(function ($datum) {
        return Icons::FILE . (new GetShortPath())($datum['path']);
      }, $state->getFiles());
      $test->addMessage((new Message($short_paths,
        MessageType::TEST_RUN_STATE_FILES,
        Verbosity::VERY_VERBOSE)));
    }
  }

  public function onTestFailed(TestEventInterface $event): void {
    $test = $event->getTest();
    $line = (string) $test;
    $why = $test->get('why');
    if ($why) {
      $line .= ": $why";
    }
    $test->addMessage(new Message([$line], MessageType::ERROR, Verbosity::VERBOSE));
  }

  public function onTestFinished(TestEventInterface $event): void {
    $this->writeTestResult($event->getTest());
  }

  private function writeTestResult(Test $test) {
    $directive = $test->getDirective();
    $icon = match ($directive) {
      Directive::SKIP => Icons::WARN,
      default => NULL,
    };
    $result = $test->getResult();
    $icon = $icon ?? match ($result) {
      Result::OK => Icons::TEST_RESULT_OK,
      Result::NOT_OK => Icons::TEST_RESULT_NOT_OKAY,
      default => Icons::TEST_RESULT_NONE,
    };
    $icon ??= '';

    $message = trim(sprintf('%s %s',
      $result->value,
      $test->getNumber(),
    ));
    $why = $test->get('why') ?? '';
    if ($why) {
      $message .= " $why";
    }
    $message .= match ($directive) {
      Directive::SKIP => ' ' . Directive::SKIP->value,
      Directive::TODO => ' ' . Directive::TODO->value,
      default => '',
    };
    $message = (new Message([$message], MessageType::TEST_RUN_STATE_RESULT))->setIcon($icon);
    $test->getRunner()->getMessenger()->writeln($message);
  }

}
