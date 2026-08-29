<?php

namespace AKlump\CheckPages\Output\Messenger;

use AKlump\CheckPages\Messaging\MessageInterface;
use AKlump\CheckPages\Output\VerboseDirective;
use AKlump\CheckPages\Messaging\MessengerInterface;

/**
 * A printer that does nothing (--quiet).
 */
class DevNullPrinter implements MessengerInterface {

  public function getVerboseDirective(): VerboseDirective {
    return new VerboseDirective(VerboseDirective::getTotalIntersection());
  }

  public function writeln(MessageInterface $message): void {
    // Do nothing e.g., > /dev/null
  }

  public function getVerbosity(): int {
    return $this->getVerboseDirective()->toInt();
  }
}
