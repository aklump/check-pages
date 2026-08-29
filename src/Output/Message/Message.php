<?php

namespace AKlump\CheckPages\Output\Message;

use AKlump\CheckPages\Output\VerboseDirective;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\CheckPages\Messaging\MessageBase;
use AKlump\CheckPages\Messaging\MessageType;
use Symfony\Component\Console\Output\OutputInterface;

class Message extends MessageBase {

  protected ?int $verbosity;

  /**
   * @var \AKlump\CheckPages\Output\VerboseDirective
   */
  private $directive;

  public function __construct(array $lines, string $message_type = NULL, int $verbosity = NULL) {
    $this->verbosity = $verbosity;
    $this->directive = VerboseDirective::createFromInt($verbosity ?? Verbosity::NORMAL);
    $message_type = $message_type ?? MessageType::NONE;
    parent::__construct($lines, $message_type);
  }

  public function getVerboseDirective(): VerboseDirective {
    return $this->directive;
  }

  public function getVerbosity(): int {
    $v = $this->verbosity ?? Verbosity::NORMAL;

    if ($v & Verbosity::DEBUG) {
      return OutputInterface::VERBOSITY_DEBUG;
    }
    if ($v & Verbosity::VERY_VERBOSE) {
      return OutputInterface::VERBOSITY_VERY_VERBOSE;
    }
    if ($v & Verbosity::VERBOSE) {
      return OutputInterface::VERBOSITY_VERBOSE;
    }

    return OutputInterface::VERBOSITY_NORMAL;
  }
}
