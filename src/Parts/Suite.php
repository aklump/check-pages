<?php

namespace AKlump\CheckPages\Parts;

class Suite implements PartInterface {

  public function __construct(string $id, array $config, Runner $runner) {
    $this->runner = $runner;
    $this->id = $id;
    $this->config = $config;
  }

  public function getRunner(): Runner {
    return $this->runner;
  }

  public function id(): string {
    return $this->id;
  }

  public function getConfig(): array {
    return $this->config;
  }

}
