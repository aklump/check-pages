<?php

namespace AKlump\CheckPages\Parts;

use AKlump\CheckPages\Variables;

class Suite implements PartInterface, \JsonSerializable {

  /**
   * @var Test[]
   */
  protected $tests = [];

  protected $autoIncrementTestId = 0;

  protected $runner;

  protected $id;

  protected $group;

  protected $config;

  protected $vars;

  public function __construct(string $id, array $config, Runner $runner) {
    $this->runner = $runner;
    $this->id = $id;
    $this->setConfig($config);
    $this->vars = new Variables();
  }

  /**
   * @return mixed
   */
  public function getGroup() {
    return $this->group;
  }

  /**
   * @param mixed $group
   *
   * @return
   *   Self for chaining.
   */
  public function setGroup($group): self {
    $this->group = $group;

    return $this;
  }

  public function getRunner(): Runner {
    return $this->runner;
  }

  public function id(): string {
    return $this->id;
  }

  public function setConfig(array $config): Suite {
    $this->config = $config;

    return $this;
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

  public function addTest(array $config): self {
    $this->tests[] = new Test(++$this->autoIncrementTestId, $config, $this);

    return $this;
  }

  public function removeTest(Test $test): self {
    $this->tests = array_values(array_filter($this->tests, function ($item) use ($test) {
      return $item->id() != $test->id();
    }));

    return $this;
  }

  /**
   * Replace a single test with multiple.
   *
   * This can be used for one to many shorthand type test configs.  This will
   * re-index all tests, meaning the test IDs will change.
   *
   * @param \AKlump\CheckPages\Parts\Test $test
   * @param array $test_configs
   *   This must be an indexed array.  Each item is an array of test config.
   *
   * @return void
   */
  public function replaceTestWithMultiple(Test $test, array $test_configs) {
    if (empty($test_configs)) {
      throw new \InvalidArgumentException('$test_configs cannot be empty');
    }
    if (!is_numeric(key($test_configs))) {
      throw new \InvalidArgumentException(sprintf('%s: $test_configs must have numeric keys. %s', __METHOD__, json_encode($test_configs)));
    }
    foreach ($this->tests as $offset => $current_test) {
      if ($current_test->id() == $test->id()) {
        break;
      }
      $offset = NULL;
    }
    if (!is_null($offset)) {
      $tests = array_map(function ($config) {
        return new Test(++$this->autoIncrementTestId, $config, $this);
      }, $test_configs);
      array_splice($this->tests, $offset, 1, $tests);
    }
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
