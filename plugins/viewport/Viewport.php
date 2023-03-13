<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Parts\SetTrait;
use AKlump\CheckPages\Parts\Test;

/**
 * Implements the Viewport plugin.
 */
final class Viewport implements PluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function getPluginId(): string {
    return 'viewport';
  }

  public static function doesApply($context): bool {
    if ($context instanceof Test) {
      return array_key_exists('viewport', $context->getConfig());
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::TEST_STARTED => [
        function (Event\TestEventInterface $event) {
          $test = $event->getTest();
          if (self::doesApply($test)) {
            $config = $test->getConfig();
            // Turn on JS so we get the right browser class.
            $config['js'] = TRUE;
            $test->setConfig($config);
          }
        },
      ],
      Event::REQUEST_CREATED => [
        function (Event\DriverEventInterface $event) {
          $test = $event->getTest();
          if (self::doesApply($test)) {
            /** @var \AKlump\CheckPages\Browser\HeadlessBrowserInterface $driver */
            $driver = $event->getDriver();
            $config = $test->getConfig()['viewport'] ?? [];
            $driver->setViewport($config['width'] ?? NULL, $config['height'] ?? NULL);
          }
        },
      ],
    ];
  }

}
