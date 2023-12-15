<?php

namespace AKlump\CheckPages\Service;

use AKlump\CheckPages\Handlers\Value;
use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Traits\SetTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use AKlump\CheckPages\Event\TestEventInterface;

class InterpolationService implements EventSubscriberInterface {

  use SetTrait;

  public static function getSubscribedEvents() {
    return [
      Event::TEST_CREATED => [
        [
          fn(TestEventInterface $event) => (new self())->interpolateTestOnly($event->getTest()),
          100,
        ],
      ],
    ];
  }

  private function interpolateTestOnly(Test $test) {
    $config = $test->getConfig();
    $keys = $this->getTestScopeInterpolationKeys();
    foreach ($keys as $key) {
      if ($test->has($key)) {
        $test->interpolate($config[$key]);
      }
    }
    $test->setConfig($config);
  }

  private function getTestScopeInterpolationKeys(): array {
    $keys = ['why'];
    $keys = array_merge($keys, (new Value())->getInterpolationKeys());

    return $keys;
  }

  private static function getAssertScopeInterpolationKeys(): array {
    return [];
  }
}
