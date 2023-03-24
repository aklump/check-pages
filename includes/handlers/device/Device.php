<?php

namespace AKlump\CheckPages\Handlers;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\DriverEventInterface;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Parts\SetTrait;

/**
 * Implements the Device handler.
 */
final class Device implements HandlerInterface {

  /**
   * {@inheritdoc}
   */
  public static function getId(): string {
    return 'device';
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::TEST_CREATED => [
        function (TestEventInterface $event) {
          $test = $event->getTest();
          if ($test->has('device')) {
            $config = $test->getConfig();
            // Turn on JS so we get the right browser class.
            $config['js'] = TRUE;
            $test->setConfig($config);
          }
        },
      ],
      Event::REQUEST_CREATED => [
        function (DriverEventInterface $event) {
          $test = $event->getTest();
          if ($test->has('device')) {
            /** @var \AKlump\CheckPages\Browser\HeadlessBrowserInterface $driver */
            $driver = $event->getDriver();
            $device = $test->get('device') ?? [];
            $driver->setViewport($device['width'] ?? NULL, $device['height'] ?? NULL, $device['pixel ratio'] ?? NULL);
          }
        },
      ],
    ];
  }

}
