<?php

namespace AKlump\CheckPages\Event;

use AKlump\CheckPages\Parts\Suite;
use Symfony\Contracts\EventDispatcher\Event;

final class OnLoadSuite extends Event implements SuiteEventInterface {

  /**
   * @var \AKlump\CheckPages\Parts\Suite
   */
  private $suite;

  public function __construct(Suite $suite) {
    $this->suite = $suite;
  }

  public function getSuite(): Suite {
    return $this->suite;
  }

}
