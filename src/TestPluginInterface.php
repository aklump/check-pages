<?php

namespace AKlump\CheckPages;

use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\Parts\Test;
use Psr\Http\Message\ResponseInterface;

/**
 * Interface TestPluginInterface.
 *
 * Give this to plugin classes that can handle assertions.
 *
 * @package AKlump\CheckPages
 */
interface TestPluginInterface {

  /**
   * @param array $config
   *
   * @return true|false|void
   *   Return nothing and the application of this plugin will be calculated
   *   based on matching the schema.  Otherwise you may override by returning
   *   true or false.
   */
  public function applies(array &$config);

  public function onBeforeSuite(Suite $suite);

  public function onBeforeTest(Test $test);

  public function onBeforeDriver(array &$config);

  public function onBeforeRequest(&$driver);

  /**
   * Prepare the assert before it's run.
   *
   * This will only be called if the plugin will handle the assertion.
   *
   * @param \AKlump\CheckPages\Assert $assert
   * @param \Psr\Http\Message\ResponseInterface $response
   *
   * @return mixed
   */
  public function onBeforeAssert(Assert $assert, ResponseInterface $response);

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
  public function onAssertToString(string $stringified, Assert $assert): string;

}
