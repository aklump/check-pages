<?php

namespace AKlump\CheckPages;

use Psr\Http\Message\ResponseInterface;

/**
 * Implements the javascript plugin.
 */
final class Javascript implements TestPluginInterface {

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
      if (is_array($assertion) && array_key_exists('javascript', $assertion)) {
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
      if (!empty($assertion[Assert::SEARCH_JAVASCRIPT])) {
        $driver->addJavascriptEval($assertion[Assert::SEARCH_JAVASCRIPT]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeAssert(Assert $assert, ResponseInterface $response) {
    list($search_type) = $assert->getSearch();
    if ($search_type === Assert::SEARCH_JAVASCRIPT) {
      $haystack = json_decode($response->getHeader('X-Javascript-Evals')[0] ?? '{}', TRUE);
      $haystack = json_encode($haystack);
      $assert->setHaystack($haystack);
    }
  }

}
