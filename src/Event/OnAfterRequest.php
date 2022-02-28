<?php

namespace AKlump\CheckPages\Event;

use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\RequestDriverInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class OnAfterRequest extends Event implements DriverEventInterface {

  /**
   * @var \AKlump\CheckPages\RequestDriverInterface
   */
  private $driver;

  public function __construct(RequestDriverInterface $driver, Test $test) {
    $this->driver = $driver;
    $this->test = $test;
  }

  public function getDriver(): RequestDriverInterface {
    return $this->driver;
  }

  public function getTest(): Test {
    return $this->test;
  }

}
