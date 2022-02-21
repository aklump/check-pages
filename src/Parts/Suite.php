<?php

namespace AKlump\CheckPages\Parts;

use AKlump\CheckPages\Variables;

class Suite implements PartInterface, \JsonSerializable {

  /**
   * @var Test[]
   */
  protected $tests = [];

  protected $runner;

  protected $id;

  protected $config;

  protected $vars;

  public function __construct(string $id, array $config, Runner $runner) {
    $this->runner = $runner;
    $this->id = $id;
    $this->config = $config;
    $this->vars = new Variables();
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

  public function variables(): Variables {
    return $this->vars;
  }

  public function getHttpMethods(): array {
    $methods = [];
    foreach ($this->tests as $test) {
      $methods[] = $test->getHttpMethod();
    }

    return array_unique($methods);
  }

  public function addTest($test_index, array $config): self {

    // Normalize config keys.
    $keys = array_map(function ($key) {
      return $key === 'visit' ? 'url' : $key;
    }, array_keys($config));
    $config = array_combine($keys, $config);
    $id = strval($test_index);
    $this->tests[$id] = new Test($id, $config, $this);

    return $this;
  }

  public function removeTest(Test $test): self {
    $this->tests = array_values(array_filter($this->tests, function ($item) use ($test) {
      return $item->id() != $test->id();
    }));

    return $this;
  }

  public function getTests(): array {
    return $this->tests;
  }

  /**
   * Remove all tests from the suite.
   *
   * @return $this
   *   Self for chaining.
   */
  public function removeAllTests(): self {
    $this->tests = [];

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    $data = [];
    foreach ($this->getTests() as $test) {
      $data[] = $test->jsonSerialize();
    }

    return $data;
  }

}
