<?php

namespace AKlump\CheckPages\Output;

use AKlump\Messaging\MessengerInterface;

/**
 * A printer that does nothing (--quiet).
 */
class DevNullPrinter implements MessengerInterface {

  public function setVerboseDirective(VerboseDirective $verbose_directive): MessengerInterface {
    return $this;
  }

  public function getVerboseDirective(): VerboseDirective {
    return new VerboseDirective(VerboseDirective::getTotalIntersection());
  }

  public function deliver(\AKlump\Messaging\MessageInterface $message, int $flags = NULL) {
    // Do nothing e.g., > /dev/null
  }

}
