<?php

namespace AKlump\CheckPages\Event;

use AKlump\CheckPages\Parts\Suite;

interface SuiteEventInterface {

  public function getSuite(): Suite;

}
