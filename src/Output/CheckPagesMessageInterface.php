<?php

namespace AKlump\CheckPages\Output;

use AKlump\Messaging\MessageInterface;

interface CheckPagesMessageInterface extends MessageInterface {

  public function getVerboseDirective(): VerboseDirective;
}
