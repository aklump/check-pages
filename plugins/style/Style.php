<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Event\AssertEventInterface;
use AKlump\CheckPages\Event\DriverEventInterface;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Assert;
use AKlump\CheckPages\SerializationTrait;

/**
 * Implements the style plugin.
 */
class Style extends LegacyPlugin {

  const SEARCH_TYPE = 'style';

  const MODIFIER_TYPE = 'style';

  /**
   * Captures the test config to share across methods.
   *
   * @var array
   */
  private $assertions;

  /**
   * {@inheritdoc}
   */
  public function onBeforeDriver(TestEventInterface $event) {
    $config = $event->getTest()->getConfig();
    if (empty($config['find'])) {
      return;
    }
    foreach ($config['find'] as $assertion) {
      if (is_array($assertion) && array_key_exists(self::SEARCH_TYPE, $assertion)) {
        $config['js'] = TRUE;
        $this->assertions[] = $assertion;
        $event->getTest()->setConfig($config);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeRequest(DriverEventInterface $event) {
    if (empty($this->assertions)) {
      return;
    }
    foreach ($this->assertions as $assertion) {
      if (!empty($assertion[Dom::SEARCH_TYPE])) {
        $event->getDriver()->addStyleRequest($assertion[Dom::SEARCH_TYPE]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeAssert(AssertEventInterface $event) {
    $assert = $event->getAssert();
    $response = $event->getDriver()->getResponse();

    $dom_path = $assert->{Dom::SEARCH_TYPE};
    $style_property = $assert->{self::MODIFIER_TYPE};
    $assert
      ->setSearch(self::SEARCH_TYPE, $dom_path)
      ->setModifer(self::MODIFIER_TYPE, $style_property);
    $haystack = json_decode($response->getHeader('X-Computed-Styles')[0] ?? '{}', TRUE);
    $assert->setHaystack([$haystack[$dom_path][$style_property] ?? NULL]);
  }

}
