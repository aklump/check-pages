<?php

namespace AKlump\CheckPages\Output\Messenger;

use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Messaging\MessageInterface;
use AKlump\CheckPages\Messaging\MessengerInterface;
use DateTimeInterface;

/**
 * Used to print messages to logfile.
 */
final class LoggerPrinter implements MessengerInterface {

  private Runner $runner;

  private string $basename;

  private string $type;

  public function __construct(string $basename, Runner $runner) {
    $this->runner = $runner;
    $this->basename = $basename;
  }

  public function writeln(MessageInterface $message): void {
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
    $time = date_create()->format(DateTimeInterface::ATOM);

    return sprintf('%s %s', $time, $line);
  }

  public function getVerbosity(): int {
    return 0;
  }

}
