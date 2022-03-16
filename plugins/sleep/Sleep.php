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
    if (is_null($sleep_seconds)) {
      $sleep_seconds = $event->getTest()->getConfig()['sleep'] ?? NULL;
    }

    if ($sleep_seconds) {
      echo sprintf("⏱  Sleep for %s second(s).", $sleep_seconds) . PHP_EOL;
      sleep($sleep_seconds);
    }
  }

}
