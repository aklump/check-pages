<?php

namespace AKlump\CheckPages\Output;

interface DebuggableInterface {

  /**
   * How this is implemented may change, however it will always follow these rules:
   *
   * - only display when user has enabled debugging.
   *
   * @param string $id
   *   An id to indicate the source/topic of the debug message.
   * @param string[] $messages
   *   One or more messages to be written when debugging is enabled.
   */
  public function debug(string $id, array $messages): void;
}
