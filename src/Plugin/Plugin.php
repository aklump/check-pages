<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Assert;
use AKlump\CheckPages\Event\AssertEventInterface;
use AKlump\CheckPages\Event\DriverEventInterface;
use AKlump\CheckPages\Event\SuiteEventInterface;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Parts\Runner;

abstract class Plugin implements PluginInterface {

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

  public function applies(array &$config) {
  }

  public function onLoadSuite(SuiteEventInterface $event) {
  }

  public function onBeforeTest(TestEventInterface $event) {
  }

  public function onBeforeDriver(TestEventInterface $event) {
  }

  public function onBeforeRequest(DriverEventInterface $event) {
  }

  public function onAfterRequest(DriverEventInterface $event) {
  }

  public function onBeforeAssert(AssertEventInterface $event) {
  }

  public function onAfterAssert(AssertEventInterface $event) {
  }

  public function onAssertToString(string $stringified, Assert $assert): string {
    return $stringified;
  }
}
