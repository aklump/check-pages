<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Event\TestEventInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use AKlump\CheckPages\Event;

/**
 * Implements the Wait plugin.
 */
final class Wait implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [
      [Event::TEST_CREATED, [self::class, 'doWait']],
    ];
  }

  public static function doWait(TestEventInterface $event) {
    $wait_seconds = $event->getTest()->getConfig()['wait'] ?? NULL;
    if ($wait_seconds) {
      echo sprintf("⏱  Wait for %s second(s).", $wait_seconds);
      sleep($wait_seconds);
    }
  }

}
