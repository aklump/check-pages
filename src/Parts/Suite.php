<?php

namespace AKlump\CheckPages\Parts;

use AKlump\CheckPages\Traits\HasRunnerTrait;
use AKlump\CheckPages\Traits\PassFailTrait;
use AKlump\CheckPages\Variables;

class Suite implements PartInterface, \JsonSerializable {

  use PassFailTrait;
  use HasRunnerTrait;

  /**
   * @var Test[]
   */
  protected $tests = [];

  protected $autoIncrementTestId = 0;

  protected $id;

  protected $group;

  protected $config;

  protected $vars;

  public function __construct(string $id, array $suite_config, Runner $runner) {
    $this->vars = new Variables();
    foreach ($runner->getConfig()['variables'] ?? [] as $key => $value) {
      $this->vars->setItem($key, $value);
    }

    $this->config = $suite_config;
    $this->setRunner($runner);
    $this->id = $id;
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

  public function id(): string {
    return $this->id;
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

  public function addTestByConfig(array $config): self {
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

  public function __toString() {
    return $this->getGroup() . '\\' . $this->id();
  }

  public function getConfig(): array {
    return $this->config;
  }

}
