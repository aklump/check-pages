<?php

namespace AKlump\CheckPages\Output;

use AKlump\Messaging\MessageInterface;
use AKlump\Messaging\MessengerInterface;

/**
 * Use this to as a master printer that will run multiple.
 *
 * You might use this for a console echo and a logger at the same time.
 */
class MultiPrinter implements MessengerInterface {

  /**
   * @param \AKlump\Messaging\MessengerInterface[] $printers
   */
  public function __construct(array $printers) {
    $this->printers = $printers;
  }

  /**
   * @inheritDoc
   */
  public function addProcessor(callable $callback): MessengerInterface {
    foreach ($this->printers as &$printer) {
      $printer->addProcessor($callback);
    }

    return $this;
  }

  /**
   * @inheritDoc
   */
  public function deliver(MessageInterface $message, int $flags = NULL) {
    foreach ($this->printers as &$printer) {
      $printer->deliver($message, $flags);
    }
  }
}
