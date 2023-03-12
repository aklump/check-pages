<?php

namespace AKlump\CheckPages\Event;

use AKlump\CheckPages\Browser\RequestDriverInterface;

interface DriverEventInterface extends TestEventInterface {

  public function getDriver(): RequestDriverInterface;

}
