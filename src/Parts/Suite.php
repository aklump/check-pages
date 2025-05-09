<?php

namespace AKlump\CheckPages\Parts;

use AKlump\CheckPages\Interfaces\HasConfigInterface;
use AKlump\CheckPages\Traits\HasConfigTrait;
use AKlump\CheckPages\Traits\HasRunnerTrait;
use AKlump\CheckPages\Traits\PassFailTrait;
use AKlump\CheckPages\Traits\SkipTrait;
use AKlump\CheckPages\Variables;
use JsonSerializable;

class Suite implements PartInterface, JsonSerializable {

  use PassFailTrait;
  use HasRunnerTrait;
  use SkipTrait;

  /**
   * @var Test[]
   */
  protected $tests = [];

  protected $autoIncrementTestId = 0;

  protected $id;

  protected $group;

  protected $vars;

  /**
   * Parse suite identifiers from it's absolute filepath.
   *
   * @param string $path_to_suites , e.g. "suites/homepage/chat.yml" or "suites/chat.yml"
   *
   * @return array
   *   An array with keys: "group" and "id".
   */
  public static function parsePath(string $path_to_suites): array {
    $parts = explode(DIRECTORY_SEPARATOR, $path_to_suites);
    $part_count = count($parts);
    $id = array_pop($parts);
    $group = '';
    if ($part_count > 2) {
      $group = array_pop($parts);
    }

    return [
      'group' => $group,
      'id' => pathinfo($id, PATHINFO_FILENAME),
    ];
  }

  public function __construct(string $id, Runner $runner) {
    $this->vars = new Variables();
    foreach ($runner->getConfig()['variables'] ?? [] as $key => $value) {
      $this->vars->setItem($key, $value);
    }
    $this->setRunner($runner);
    $this->id = $id;
  }

  /**
   * @return string
   */
  public function getGroup(): string {
    return $this->group ?? '';
  }

  /**
   * @param string $group
   *
   * @return
   *   Self for chaining.
   */
  public function setGroup(string $group): self {
    $this->group = $group;

    return $this;
  }

  public function id(): string {
    return $this->id;
  }

  public function variables(): Variables {
    return $this->vars;
  }

  /**
   * Interpolate variables of all scopes on a value.
   *
   * @param $value
   *
   * @return void
   */
  public function interpolate(&$value): void {
    $this->variables()->interpolate($value);
  }

  public function getHttpMethods(): array {
    $methods = [];
    foreach ($this->tests as $test) {
      $methods[] = $test->getHttpMethod();
    }

    return array_unique($methods);
  }

  /**
   * Add a new test to the suite based on the given test array.
   *
   * Auto-assigns the test ID in a sequential manner relative to the suite.
   *
   * @param array $config
   *   The configuration array for the new test.
   *
   * @return self
   *   Self for chaining.
   */
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

  public function getConfig(): array {
    return array_map(fn(Test $test) => $test->getConfig(), $this->getTests());
  }

  /**
   * Get the next test which has neither passed nor failed.
   *
   * In a suite, tests can be added/removed dynamically during the running of
   * the suite by event handlers.  This is why this method is necessary over a
   * simple for each loop.  Every time this is called the current test set will
   * be read from first to last and the first test not yet run will be returned.
   *
   * @return \AKlump\CheckPages\Parts\Test|null
   */
  public function getNextPendingTest(): ?Test {
    foreach ($this->tests as $test) {
      if (!$test->hasPassed() && !$test->hasFailed()) {
        return $test;
      }
    }

    return NULL;
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
    return ltrim($this->getGroup() . '/' . $this->id(), '/');
  }

  /**
   * @return string
   *   A string to use as a filepath that represents this suite.
   */
  public function toFilepath(): string {
    $parts = [$this->getGroup(), $this->id()];
    $parts = preg_replace('/[ .\-\/]/', '_', $parts);

    return ltrim(implode('/', $parts), '/');
  }

}
