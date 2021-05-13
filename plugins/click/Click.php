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
  private $config;

  /**
   * {@inheritdoc}
   */
  public function onBeforeDriver(array &$config) {
    $config['js'] = TRUE;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeRequest(&$driver) {
    foreach ($this->config['find'] as $item) {
      if (isset($item['click'])) {
        $eval = "document.querySelector('{$item['click']}').click()";
        $driver->addJavascriptEval($eval);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeAssert(Assert $assert, ResponseInterface $response) {
    $haystack = json_decode($response->getHeader('X-Javascript-Evals')[0] ?? '{}', TRUE);
    $haystack = json_encode($haystack);
    $assert->setHaystack($haystack);
  }

}
