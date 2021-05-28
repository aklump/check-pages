<?php

namespace AKlump\CheckPages;

use Psr\Http\Message\ResponseInterface;

/**
 * Implements the Header plugin.
 */
final class Header implements TestPluginInterface {

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
  public function onBeforeDriver(array &$config) {
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
  public function onBeforeRequest(&$driver) {
    if (empty($this->assertions)) {
      return;
    }
    foreach ($this->assertions as $assertion) {
      if (!empty($assertion[self::SEARCH_TYPE])) {
        // TODO Do something to the driver?
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeAssert(Assert $assert, ResponseInterface $response) {
    $search_value = $assert->{self::SEARCH_TYPE};
    $assert->setSearch(self::SEARCH_TYPE, $search_value);
    $assert->setHaystack($response->getHeader($search_value));
  }

  /**
   * {@inheritdoc}
   */
  public function onAssertToString(string $stringified, Assert $assert): string {
    list(, $header) = $assert->getSearch();
    list($type, $value) = $assert->getAssertion();
    switch ($type) {
      case Assert::ASSERT_MATCH:
        return sprintf('Assert response header "%s" matches RegEx "%s".', $header, $value);

      case Assert::ASSERT_EXACT:
        return sprintf('Assert response header "%s" has the exact value "%s".', $header, $value);

      case Assert::ASSERT_COUNT:
        return sprintf('Assert the number of "%s" response headers is %s.', $header, $value);

      case Assert::ASSERT_SUBSTRING:
        return sprintf('Assert response header "%s" %s "%s".', $header, $type, $value);
    }

    return $stringified;
  }

}
