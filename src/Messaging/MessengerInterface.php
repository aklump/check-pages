<?php

namespace AKlump\CheckPages\Messaging;

interface MessengerInterface {

  public function writeln(MessageInterface $message): void;

  /**
   * @return int
   *
   * @see \AKlump\CheckPages\Output\Verbosity
   */
  public function getVerbosity(): int;

}
