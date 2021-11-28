<?php

namespace AKlump\CheckPages;

abstract class Plugin implements TestPluginInterface {

  /**
   * @var \AKlump\CheckPages\Parts\Runner
   */
  protected $runner;

  /**
   * @param \AKlump\CheckPages\Parts\Runner $runner
   *   A test runner instance.
   */
  public function __construct(\AKlump\CheckPages\Parts\Runner $runner) {
    $this->runner = $runner;
  }

  /**
   * @inheritDoc
   */
  public function applies(array &$config) {
    // TODO: Implement applies() method.
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
  public function onBeforeAssert(Assert $assert, \Psr\Http\Message\ResponseInterface $response) {
    // TODO: Implement onBeforeAssert() method.
  }

  /**
   * @inheritDoc
   */
  public function onAssertToString(string $stringified, Assert $assert): string {
    return $stringified;
  }
}
