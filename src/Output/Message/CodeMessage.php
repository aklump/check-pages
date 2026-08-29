<?php

namespace AKlump\CheckPages\Output\Message;

use AKlump\CheckPages\Output\Verbosity;
use AKlump\CheckPages\Messaging\MessageType;

/**
 * Shorthand for creating a themed-debug message.
 */
class CodeMessage extends Message {

  public function __construct(array $lines, string $language = '', int $verbosity = Verbosity::DEBUG) {
    $this->handleLanguageMarker($lines, $language);
    parent::__construct(
      $lines,
      MessageType::CODE,
      $verbosity,
    );

    //
    //
    //    $lines = array_values($lines);
    //    if ('' !== trim($lines[0] ?? '')) {
    //      array_unshift($lines, '');
    //    }
    //
    //    $this->handleLanguageMarker($lines, $language);
    //
    //    $countLines = function () use (&$lines, &$count, &$last_key) {
    //      $count = count($lines);
    //      $last_key = $count - 1;
    //    };
    //
    //    $countLines();
    //
    //    while ($count < 3 || '' !== trim($lines[$last_key] ?? '')) {
    //      $lines[] = '';
    //      $countLines();
    //    }
    //
    //    parent::__construct(
    //      $lines,
    //      MessageType::CODE,
    //      Verbosity::DEBUG
    //    );
  }

  private function handleLanguageMarker(array &$lines, string $language) {
    $language = trim($language);
    if ('' === $language) {
      return;
    }
    $prefix = '';
    switch (strtolower($language)) {
      case 'js':
      case 'javascript':
        $prefix = "JavaScript";
        break;
    }
    if ($prefix) {
      array_unshift($lines, $prefix);
    }
  }

}
