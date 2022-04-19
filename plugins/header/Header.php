<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Event\AssertEventInterface;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Assert;
use AKlump\CheckPages\SerializationTrait;

/**
 * Implements the Header plugin.
 */
final class Header extends LegacyPlugin {

  const SEARCH_TYPE = 'header';

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
    if (!is_array($config) || empty($config['find'])) {
      return;
    }
    foreach ($config['find'] as $assertion) {
      if (is_array($assertion) && array_key_exists(self::SEARCH_TYPE, $assertion)) {
        $this->assertions[] = $assertion;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeAssert(AssertEventInterface $event) {
    $assert = $event->getAssert();
    $search_value = $assert->{self::SEARCH_TYPE};
    $assert->setSearch(self::SEARCH_TYPE, $search_value);
    $assert->setHaystack($event->getDriver()
      ->getResponse()
      ->getHeader($search_value));
  }

  /**
   * {@inheritdoc}
   */
  public function onAssertToString(string $stringified, Assert $assert): string {
    list(, $header) = $assert->getSearch();
    list($type, $value) = $assert->getAssertion();
    switch ($type) {
      case Assert::ASSERT_MATCHES:
        return sprintf('Assert response header "%s" matches "%s".', $header, $value);

      case Assert::ASSERT_NOT_MATCHES:
        return sprintf('Assert response header "%s" does not match "%s".', $header, $value);

      case Assert::ASSERT_EQUALS:
        return sprintf('Assert response header "%s" is "%s".', $header, $value);

      case Assert::ASSERT_NOT_EQUALS:
        return sprintf('Assert response header "%s" is not "%s".', $header, $value);

      case Assert::ASSERT_CONTAINS:
        return sprintf('Assert response header "%s" %s "%s".', $header, $type, $value);
      default:
        if (is_numeric($assert->count) || $assert->count) {
          return sprintf('Count response headers with value "%s".', $header, $assert->count);
        }
        break;
    }

    return $stringified;
  }

}
