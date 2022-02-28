<?php

namespace AKlump\CheckPages\Event;

use AKlump\CheckPages\RequestDriverInterface;

interface DriverEventInterface extends TestEventInterface {

  public function getDriver(): RequestDriverInterface;

}
