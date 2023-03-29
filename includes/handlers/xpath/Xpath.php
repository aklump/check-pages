<?php

namespace AKlump\CheckPages\Handlers;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\AssertEventInterface;
use AKlump\CheckPages\Exceptions\TestFailedException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Implements the Xpath handler.
 */
final class Xpath implements HandlerInterface {

  /**
   * {@inheritdoc}
   */
  public static function getId(): string {
    return 'xpath';
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::ASSERT_CREATED => [
        function (AssertEventInterface $event) {
          $assert = $event->getAssert();
          if (!$assert->has('xpath')) {
            return;
          }
          try {
            $xpath = $assert->get('xpath');
            $assert->getTest()->interpolate($xpath);
            $assert->setSearch(self::getId(), $xpath);

            // Replace the current haystack with whatever we find at our xpath.
            $haystack = $assert->getHaystack();
            if (!$haystack instanceof Crawler) {
              $haystack = new Crawler($haystack);
            }
            $haystack = $haystack->filterXPath($xpath);
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
