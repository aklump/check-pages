<?php

namespace AKlump\CheckPages;

use Psr\Http\Message\ResponseInterface;

/**
 * Implements the style plugin.
 */
class Style implements TestPluginInterface {

  const SEARCH_TYPE = 'style';

  const MODIFIER_TYPE = 'property';

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
        $driver->addStyleRequest($assertion[self::SEARCH_TYPE]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeAssert(Assert $assert, ResponseInterface $response) {
    $search_value = $assert->{self::SEARCH_TYPE};
    $modifier_value = $assert->{self::MODIFIER_TYPE};
    $assert
      ->setSearch(self::SEARCH_TYPE, $search_value)
      ->setModifer(self::MODIFIER_TYPE, $modifier_value);
    $haystack = json_decode($response->getHeader('X-Computed-Styles')[0] ?? '{}', TRUE);
    $assert->setHaystack([$haystack[$search_value][$modifier_value] ?? NULL]);
  }

}
