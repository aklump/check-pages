<?php

namespace AKlump\CheckPages\Mixins\MyMixin;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\SuiteEventInterface;
use AKlump\CheckPages\Event\TestEventInterface;

/**
 * Exposes environment variables as variables in your tests.
 *
 * All env vars will be available as ENV.{name}, e.g. ENV.FOOBAR.
 *
 * Usage in runner.php:
 *
 * @code
 * add_mixin('mixins/env_vars');
 * @endcode
 *
 * Usage in suites, given a bash: $ export CP_USERNAME=foobar
 *
 * @code
 * -
 *   visit: /
 *   user: ${ENV.CP_USERNAME}
 * @endcode
 */

respond_to(Event::SUITE_CREATED, function (SuiteEventInterface $event) {
  $suite = $event->getSuite();
  $variables = $suite->variables();
  $env_vars = getenv();
  foreach ($env_vars as $key => $value) {
    $variables->setItem("ENV.$key", $value);
  }
  // TODO If there was a Suite::setConfig() I could do the interpolation here, which seems better!
});

respond_to(Event::TEST_CREATED, function (TestEventInterface $event) {
  $test = $event->getTest();
  $config = $test->getConfig();
  $test->interpolate($config);
  $test->setConfig($config);
});
