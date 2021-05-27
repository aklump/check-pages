<?php

namespace AKlump\CheckPages;

use Psr\Http\Message\ResponseInterface;

/**
 * Interface TestPluginInterface.
 *
 * Give this to plugin classes that can handle assertions.
 *
 * @package AKlump\CheckPages
 */
interface TestPluginInterface {

  public function onBeforeDriver(array &$config);

  public function onBeforeRequest(&$driver);

  public function onBeforeAssert(Assert $assert, ResponseInterface $response);

}
