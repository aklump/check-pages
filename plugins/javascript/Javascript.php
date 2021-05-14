<?php

namespace AKlump\CheckPages;

use Psr\Http\Message\ResponseInterface;

/**
 * Implements the javascript plugin.
 */
final class Javascript implements TestPluginInterface {

  const SEARCH_TYPE = 'javascript';

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
      if (is_array($assertion) && array_key_exists(self::SEARCH_TYPE, $assertion)) {
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
      if (!empty($assertion[self::SEARCH_TYPE])) {
        $driver->addJavascriptEval($assertion[self::SEARCH_TYPE]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeAssert(Assert $assert, ResponseInterface $response) {
    $search_value = $assert->{self::SEARCH_TYPE};
    $assert->setSearch(self::SEARCH_TYPE, $search_value);
    $haystack = json_decode($response->getHeader('X-Javascript-Evals')[0] ?? '{}', TRUE);
    $haystack = array_reduce($haystack, function ($result, $item) use ($search_value) {
      if ($search_value === $item['eval']) {
        $result .= $item['result'];
      }

      return $result;
    });

    $assert->setHaystack([$haystack]);
  }

}
