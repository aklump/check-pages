<?php

namespace AKlump\CheckPages\Event;

use AKlump\CheckPages\Assert;

interface AssertEventInterface extends DriverEventInterface {

  public function getAssert(): Assert;

}
