<?php

namespace AKlump\CheckPages\Output\Message;

use AKlump\CheckPages\Output\Verbosity;
use AKlump\CheckPages\Messaging\MessageType;

class Divider extends Message {

  public function __construct() {
    parent::__construct(['---'], MessageType::DIVIDER, Verbosity::VERY_VERBOSE);
  }

}
