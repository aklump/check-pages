<?php

namespace AKlump\CheckPages\Output;

use AKlump\Messaging\MessageType;

/**
 * Shorthand for creating a themed-debug message.
 */
class DebugMessage extends Message {

  public function __construct(array $lines) {

    // Add an icon to the first line.
    $first_line = array_shift($lines);
    $head = ["🐞 $first_line"];
    if (count($lines)) {
      $head[] = '';
    }
    $lines = array_merge($head, $lines);

    parent::__construct(
      $lines,
      MessageType::DEBUG,
      new VerboseDirective('D')
    );
  }

}
