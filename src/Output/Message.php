<?php

namespace AKlump\CheckPages\Output;

use AKlump\Messaging\MessageBase;

class Message extends MessageBase {

  /**
   * @var \AKlump\CheckPages\Output\VerboseDirective
   */
  private $directive;

  public function __construct(array $lines, string $message_type = NULL, VerboseDirective $directive = NULL) {
    $this->directive = $directive ?? new VerboseDirective('');
    parent::__construct($lines, $message_type);
  }

  public function getVerboseDirective(): VerboseDirective {
    return $this->directive;
  }

}
