<?php

namespace AKlump\CheckPages\Output;

use AKlump\Messaging\MessengerInterface;

/**
 * A printer that does nothing (--quiet).
 */
class DevNullPrinter implements MessengerInterface {

  public function getVerboseDirective(): VerboseDirective {
    return new VerboseDirective(VerboseDirective::getTotalIntersection());
  }

  public function deliver(\AKlump\Messaging\MessageInterface $message, int $flags = NULL) {
    // Do nothing e.g., > /dev/null
  }

  public function addProcessor(callable $callback): MessengerInterface {
    return $this;
  }
}
