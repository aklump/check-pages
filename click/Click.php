<?php

namespace AKlump\CheckPages;

use Psr\Http\Message\ResponseInterface;

/**
 * Implements the click plugin.
 */
final class Click implements TestPluginInterface {

  /**
   * Captures the test config to share across methods.
   *
   * @var array
   */
  private $assertions;

  /**
   * {@inheritdoc}
   */
  public function onBeforeDriver(array &$config) {
    if (!is_array($config) || empty($config['find'])) {
      return;
    }
    foreach ($config['find'] as $assertion) {
      if (is_array($assertion) && array_key_exists('click', $assertion)) {
        $config['js'] = TRUE;
        $this->assertions[] = $assertion;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeRequest(&$driver) {
    if (empty($this->assertions)) {
      return;
    }
    foreach ($this->assertions as $assertion) {
      if (isset($assertion['click'])) {
        $eval = "document.querySelector('{$assertion['click']}').click()";
        $driver->addJavascriptEval($eval);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeAssert(Assert $assert, ResponseInterface $response) {

  }

}
