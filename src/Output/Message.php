<?php

namespace AKlump\CheckPages\Output;

use AKlump\Messaging\MessageBase;
use AKlump\Messaging\MessageType;

class Message extends MessageBase {

  /**
   * @var \AKlump\CheckPages\Output\VerboseDirective
   */
  private $directive;

  public function __construct(array $lines, string $message_type = NULL, int $verbosity = NULL) {
    $this->setVerbosity($verbosity ?? Verbosity::NORMAL);
    $message_type = $message_type ?? MessageType::INFO;
    parent::__construct($lines, $message_type);
  }

  public function getVerboseDirective(): VerboseDirective {
    return $this->directive;
  }

  private function setVerbosity(int $verbosity) {
    $directive = '';
    if ($verbosity & Verbosity::DEBUG) {
      $directive .= 'D';
    }
    if ($verbosity & Verbosity::VERBOSE) {
      $directive .= 'V';
    }
    if ($verbosity & Verbosity::HEADERS) {
      $directive .= 'H';
    }
    if ($verbosity & Verbosity::REQUEST) {
      $directive .= 'S';
    }
    if ($verbosity & Verbosity::RESPONSE) {
      $directive .= 'R';
    }

    $this->directive = new VerboseDirective($directive);
  }

}
