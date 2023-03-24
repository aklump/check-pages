<?php

namespace AKlump\CheckPages\Handlers;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\AssertEventInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Implements the Dom handler.
 */
final class Dom implements HandlerInterface {

  /**
   * @var string
   */
  const SELECTOR = 'dom';

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::ASSERT_CREATED => [
        function (AssertEventInterface $event) {
          $config = $event->getAssert()->getConfig();
          $should_apply = array_key_exists(self::SELECTOR, $config);
          if ($should_apply) {
            $assert = $event->getAssert();
            $search_value = $assert->{self::SELECTOR};
            $assert->setSearch(self::getId(), $search_value);
            $haystack = $assert->getHaystack();
            if (!$haystack instanceof Crawler) {
              $haystack = new Crawler($haystack);
            }
            $haystack = $haystack->filter($search_value);
            $assert->setHaystack($haystack);
          }
        },
      ],
    ];
  }

  public static function getId(): string {
    return 'dom';
  }

}
