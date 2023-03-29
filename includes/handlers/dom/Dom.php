<?php

namespace AKlump\CheckPages\Handlers;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\AssertEventInterface;
use AKlump\CheckPages\Exceptions\TestFailedException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Implements the Dom handler.
 */
final class Dom implements HandlerInterface {

  /**
   * @var string
   */
  const SELECTOR = 'dom';

  public static function getId(): string {
    return 'dom';
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::ASSERT_CREATED => [
        function (AssertEventInterface $event) {
          $assert = $event->getAssert();
          if (!$assert->has(self::SELECTOR)) {
            return;
          }
          try {
            $search_value = $assert->get(self::SELECTOR);
            $assert->getTest()->interpolate($search_value);
            $assert->setSearch(self::getId(), $search_value);

            // Replace the current haystack with whatever we find at our DOM.
            $haystack = $assert->getHaystack();
            if (!$haystack instanceof Crawler) {
              $haystack = new Crawler($haystack);
            }
            $haystack = $haystack->filter($search_value);
            $assert->setHaystack($haystack);
          }
          catch (\Exception $e) {
            throw new TestFailedException($event->getTest()->getConfig(), $e);
          }
        },
      ],
    ];
  }

}
