<?php

namespace AKlump\CheckPages\Event;

use AKlump\CheckPages\Parts\Test;

interface TestEventInterface {

  public function getTest(): Test;
}
