<?php

namespace AKlump\CheckPages\Event;

use AKlump\CheckPages\Parts\Test;
use Symfony\Contracts\EventDispatcher\Event;

final class OnBeforeDriver extends Event implements TestEventInterface {

  /**
   * @var \AKlump\CheckPages\Parts\Test
   */
  private $test;

  public function __construct(Test $test) {
    $this->test = $test;
  }

  public function getTest(): Test {
    return $this->test;
  }

}
