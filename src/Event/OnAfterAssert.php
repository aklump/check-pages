<?php

namespace AKlump\CheckPages\Event;

use AKlump\CheckPages\Assert;
use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\RequestDriverInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class OnAfterAssert extends Event implements AssertEventInterface {

  /**
   * @var \AKlump\CheckPages\Assert
   */
  private $assert;

  /**
   * @var \AKlump\CheckPages\RequestDriverInterface
   */
  private $driver;

  /**
   * @var \AKlump\CheckPages\Parts\Test
   */
  private $test;

  public function __construct(Assert $assert, Test $test, RequestDriverInterface $driver) {
    $this->assert = $assert;
    $this->test = $test;
    $this->driver = $driver;
  }

  public function getAssert(): Assert {
    return $this->assert;
  }

  public function getDriver(): RequestDriverInterface {
    return $this->driver;
  }

  public function getTest(): Test {
    return $this->test;
  }
}
