<?php

namespace AKlump\CheckPages\Parts;

class Suite implements PartInterface {

  /**
   * @var Test[]
   */
  protected $tests = [];

  public function __construct(string $id, array $config, Runner $runner) {
    $this->runner = $runner;
    $this->id = $id;
    $this->config = $config;
    $this->vars = new \AKlump\CheckPages\Variables();
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

  public function variables(): \AKlump\CheckPages\Variables {
    return $this->vars;
  }

  public function getHttpMethods(): array {
    $methods = [];
    foreach ($this->tests as $test) {
      $methods[] = $test->getHttpMethod();
    }

    return array_unique($methods);
  }

  public function addTest(Test $test): self {
    $this->tests[$test->id()] = $test;

    return $this;
  }

  public function getTests(): array {
    return $this->tests;
  }

}
