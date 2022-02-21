<?php

namespace AKlump\CheckPages;

use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\Parts\Test;
use Psr\Http\Message\ResponseInterface;

abstract class Plugin implements TestPluginInterface {

  /**
   * @var \AKlump\CheckPages\Parts\Runner
   */
  protected $runner;

  /**
   * @param \AKlump\CheckPages\Parts\Runner $runner
   *   A test runner instance.
   */
  public function __construct(Runner $runner) {
    $this->runner = $runner;
  }

  /**
   * @inheritDoc
   */
  public function applies(array &$config) {
    // TODO: Implement applies() method.
  }

  public function onLoadSuite(Suite $suite) {
    // TODO: Implement onBeforeDriver() method.
  }

  public function onBeforeTest(Test $test) {
    // TODO: Implement onBeforeDriver() method.
  }

  public function onBeforeDriver(array &$config) {
    // TODO: Implement onBeforeDriver() method.
  }

  public function onBeforeRequest(&$driver) {
    // TODO: Implement onBeforeRequest() method.
  }

  /**
   * @inheritDoc
   */
  public function onBeforeAssert(Assert $assert, ResponseInterface $response) {
    // TODO: Implement onBeforeAssert() method.
  }

  /**
   * @inheritDoc
   */
  public function onAssertToString(string $stringified, Assert $assert): string {
    return $stringified;
  }
}
