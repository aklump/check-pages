<?php

namespace AKlump\CheckPages\Event;

use AKlump\CheckPages\Parts\Test;
use Symfony\Contracts\EventDispatcher\Event;

final class TestEvent extends Event implements TestEventInterface {

  private $test;

  private $icons = [];

  public function __construct(Test $test) {
    $this->test = $test;
  }

  public function getTest(): Test {
    return $this->test;
  }

  public function getIcons(): array {
    return $this->icons;
  }

  public function addIcon(string $icon): void {
    $this->icons[] = $icon;
  }

}
