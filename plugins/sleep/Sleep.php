<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Event\TestEventInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use AKlump\CheckPages\Event;

/**
 * Implements the Sleep plugin.
 */
final class Sleep implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [
      [Event::TEST_CREATED, [self::class, 'doSleep']],
    ];
  }

  public static function doSleep(TestEventInterface $event) {
    $sleep_seconds = $event->getTest()->getConfig()['sleep'] ?? NULL;
    if ($sleep_seconds) {
      sleep($sleep_seconds);
    }
  }

}
