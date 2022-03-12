<?php

namespace AKlump\CheckPages\Event;

interface RunnerEventInterface {

  public function getRunner(): \AKlump\CheckPages\Parts\Runner;
}
