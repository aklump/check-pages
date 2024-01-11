<?php
/**
 * @file Demonstrate the usage of add_mixin().
 */

namespace AKlump\CheckPages\Mixins\Foo;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\TestEventInterface;

$base_url = config_get('base_url');
assert(is_string($base_url), new \AKlump\CheckPages\Exceptions\StopRunnerException());

respond_to(Event::TEST_CREATED, function (TestEventInterface $event) {
  $test = $event->getTest();
  if (!$test->has('set_foo_variable_to')) {
    return;
  }
  $value = $test->get('set_foo_variable_to');
  $test->getSuite()->variables()->setItem('foo', $value);
});
