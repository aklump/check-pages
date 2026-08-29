<?php

namespace AKlump\CheckPages\Output\Message;

use AKlump\CheckPages\Messaging\MessageType;

class NewLine extends Message {

  public function __construct(int $verbosity, int $count = 1) {
    parent::__construct(array_fill(0, $count, ''), MessageType::NONE, $verbosity);
  }
}
