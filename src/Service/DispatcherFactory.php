<?php

namespace AKlump\CheckPages\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DispatcherFactory {

  public static function createFromContainer(ContainerInterface $container): EventDispatcherInterface {
    $dispatcher = new EventDispatcher();
    $serviceIds = $container->findTaggedServiceIds('event_subscriber');
    foreach ($serviceIds as $serviceId => $tags) {
      $subscribed_events = $container->get($serviceId)
        ->getSubscribedEvents();
      self::addSubscribedEvents($dispatcher, $subscribed_events);
    }

    return $dispatcher;
  }

  /**
   * To add listeners use ::addListener() method to the return instance.
   *
   * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  public static function create(): EventDispatcherInterface {
    return new EventDispatcher();
  }

  /**
   * Add the return value of getSubscribedEvents to an existing dispatcher.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   * @param array $subscribed_events
   *   These will be normalized then added.
   *
   * @return void
   */
  public static function addSubscribedEvents(EventDispatcherInterface $dispatcher, array $subscribed_events) {
    $subscribed_events = array_map([
      self::class,
      'normalizeListener',
    ], $subscribed_events);
    foreach ($subscribed_events as $event_name => $listeners) {
      foreach ($listeners as $listener) {
        list($callback, $priority) = $listener;
        $dispatcher->addListener($event_name, $callback, $priority);
      }
    }
  }

  private static function normalizeListener($listener) {
    $priority = 0;
    if (isset($listener[1]) && is_numeric($listener[1])) {
      $priority = $listener[1];
    }

    if (is_callable($listener)) {
      $listener = [[$listener, $priority]];
    }
    elseif (is_callable($listener[0])) {
      $listener = [[$listener[0], $priority]];
    }

    return $listener;
  }

}
