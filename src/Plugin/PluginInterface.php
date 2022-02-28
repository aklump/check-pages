<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Event\AssertEventInterface;
use AKlump\CheckPages\Event\DriverEventInterface;
use AKlump\CheckPages\Event\SuiteEventInterface;
use AKlump\CheckPages\Event\TestEventInterface;

/**
 * Interface PluginInterface.
 *
 * Give this to plugin classes that can handle assertions.
 *
 * @package AKlump\CheckPages
 */
interface PluginInterface {

  /**
   * @param array $config
   *
   * @return true|false|void
   *   Return nothing and the application of this plugin will be calculated
   *   based on matching the schema.  Otherwise you may override by returning
   *   true or false.
   */
  public function applies(array &$config);

  public function onLoadSuite(SuiteEventInterface $event);

  public function onBeforeTest(TestEventInterface $event);

  public function onBeforeDriver(TestEventInterface $event);

  public function onBeforeRequest(DriverEventInterface $event);

  public function onAfterRequest(DriverEventInterface $event);

  public function onBeforeAssert(AssertEventInterface $event);

  public function onAfterAssert(AssertEventInterface $event);

  /**
   * Allow the plugin to control the stringification of the Assert instance.
   *
   * The Assert is converted to a string in the output of the suite run, the
   * plugin should provide a clear statement of what is being asserted with
   * actual values inserted, e.g. 'Assert the response header "content-type"
   * contains "text/html".'
   *
   * @param string $stringified
   *   The  stringified version of $assert.  If nothing else return this value.
   *   You may also modify it as determined appropriate.
   * @param \AKlump\CheckPages\Assert $assert
   *   The assert instance.
   *
   * @return string
   *   The stringified version of $assert.
   */
  public function onAssertToString(string $stringified, \AKlump\CheckPages\Assert $assert): string;

}
