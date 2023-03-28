<?php

namespace AKlump\CheckPages\Handlers;

use AKlump\CheckPages\Assert;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\AssertEventInterface;
use AKlump\CheckPages\Parts\SetTrait;

/**
 * Implements the Header handler.
 */
final class Header implements HandlerInterface {

  /**
   * {@inheritdoc}
   */
  public static function getId(): string {
    return 'header';
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::ASSERT_CREATED => [
        function (AssertEventInterface $event) {
          $assert = $event->getAssert();
          if (!$assert->has('header')) {
            return;
          }
          $header_name = $assert->get('header');
          $assert->setSearch('header', $header_name);
          $header_value = $event->getDriver()
            ->getResponse()
            ->getHeader($header_name);
          $assert->setHaystack($header_value);
        },
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function onAssertToString(string $stringified, Assert $assert): string {
    // TODO This is from legacy plugin; need to update if I want to keep it.
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
