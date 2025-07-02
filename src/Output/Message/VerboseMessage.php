<?php

namespace AKlump\CheckPages\Output\Message;

use AKlump\CheckPages\Output\VerboseDirective;

/**
 * A class that represents a verbose message.
 */
class VerboseMessage extends Message {

  public function getVerboseDirective(): VerboseDirective {
    return $this->verbose ?? new VerboseDirective('V');
  }

}
