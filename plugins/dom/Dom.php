<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\AssertEventInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Implements the Dom plugin.
 */
final class Dom implements PluginInterface {

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
            $assert->setSearch(self::getPluginId(), $search_value);
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

  public static function getPluginId(): string {
    return 'dom';
  }

}
