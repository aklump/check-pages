<?php

namespace AKlump\CheckPages\Output\Messenger;

use AKlump\CheckPages\Parts\Runner;
use AKlump\Messaging\MessageInterface;
use AKlump\Messaging\MessengerInterface;
use DateTimeInterface;

/**
 * Used to print messages to logfile.
 */
final class LoggerPrinter implements MessengerInterface {

  /**
   * @var \AKlump\CheckPages\Parts\Runner
   */
  private $runner;

  /**
   * @var string
   */
  private $basename;

  private string $type;

  public function __construct(string $basename, Runner $runner) {
    $this->runner = $runner;
    $this->basename = $basename;
  }

  /**
   * @inheritDoc
   */
  public function addProcessor(callable $callback): MessengerInterface {
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function deliver(MessageInterface $message, int $flags = NULL) {
    $lines = $message->getMessage();
    $this->type = $message->getMessageType();
    $lines = array_map([$this, 'addMessageType'], $lines);
    $lines = array_map([$this, 'addDates'], $lines);
    $this->runner->writeToFile($this->basename, $lines);
  }

  private function addMessageType(string $line) {
    return sprintf('[%s] %s', $this->type, $line);
  }

  private function addDates($line) {
    $time = date_create()->format(DateTimeInterface::ISO8601);

    return sprintf('%s %s', $time, $line);
  }

}
