<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\AssertEventInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Implements the Dom plugin.
 */
final class Dom implements EventSubscriberInterface {

  /**
   * @var string
   */
  const SEARCH_TYPE = 'dom';

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::ASSERT_CREATED => [
        function (AssertEventInterface $event) {
          $config = $event->getAssert()->getConfig();
          $should_apply = array_key_exists(self::SEARCH_TYPE, $config);
          if ($should_apply) {
            $assert = $event->getAssert();
            $search_value = $assert->{self::SEARCH_TYPE};
            $assert->setSearch(self::SEARCH_TYPE, $search_value);
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

}
