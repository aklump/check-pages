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
    $this->directive = VerboseDirective::createFromInt($verbosity ?? Verbosity::NORMAL);
    $message_type = $message_type ?? MessageType::INFO;
    parent::__construct($lines, $message_type);
  }

  public function getVerboseDirective(): VerboseDirective {
    return $this->directive;
  }

}
