<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Exceptions\BadSyntaxException;
use AKlump\CheckPages\Variables;
use AKlump\CheckPages\Parts\Test;

/**
 * An object to contain a single loop instance.
 */
final class LoopCurrentLoop {

  /**
   * @var \AKlump\CheckPages\Parts\Test[]
   */
  private $tests = [];

  /**
   * @var array|object
   */
  private $definition;

  /**
   * @var int
   */
  private $count;

  public function __construct($definition) {

    // Expand string shorthand.
    if (is_string($definition)) {
      if (preg_match('/^(\d+)\.\.\.(\d+)$/', $definition, $matches)) {
        $definition = range($matches[1], $matches[2]);
      }
      elseif (preg_match('/^(\d+)x$/i', $definition, $matches)) {
        $definition = range(1, $matches[1]);
      }
      else {
        throw new BadSyntaxException(sprintf('Invalid loop expression: %s', $definition));
      }
      $this->count = count($definition);
      $this->definition = $definition;
    }
    else {
      $this->count = count($definition);

      // Sort out indexed arrays, and relative arrays as objects.
      $this->definition = json_decode(json_encode($definition));
    }

    // Convert arrays to 1-based indexes.
    if (is_array($this->definition)) {
      $this->definition = array_combine(range(1, $this->count), $this->definition);
    }
  }

  /**
   * Add a test to the loop instance.
   *
   * @param \AKlump\CheckPages\Parts\Test $test
   *
   * @return void
   */
  public function addTest(Test $test) {
    $this->tests[] = $test;
  }

  /**
   * Execute the loop and return the test configurations.
   *
   * @return array
   *   An array of Test configurations spawned during the loop execution.
   *
   * @see \AKlump\CheckPages\Parts\Suite::replaceTestWithMultiple()
   */
  public function execute(): array {
    /** @var \AKlump\CheckPages\Variables $variables */
    $key_key = is_object($this->definition) ? 'property' : 'index';

    $iterations = [];
    $counter = 1;
    foreach ($this->definition as $key => $value) {
      $variables = new Variables();
      $variables->setItem('loop.length', $this->count);
      $variables->setItem('loop.' . $key_key, $key);
      if (is_array($this->definition)) {
        $variables->setItem('loop.index0', $key - 1);
      }
      $variables->setItem('loop.value', $value);
      $variables->setItem('loop.last', $counter++ === $this->count);
      foreach ($this->tests as $test) {
        $config = $test->getConfig();
        // Because we're only interpolating with loop variables, the entire
        // config should be processed; including the find array.  So in this
        // case we should not use Test::interpolate().
        $variables->interpolate($config);
        $iterations[] = $config;
      }
    }

    return $iterations;
  }

}
