<?php

namespace AKlump\CheckPages\Event;

use AKlump\CheckPages\Parts\Runner;

interface RunnerEventInterface {

  public function getRunner(): Runner;
}
