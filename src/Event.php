<?php

namespace AKlump\CheckPages;

/**
 * Defines the event names dispatched by Check Pages.
 *
 * Constants are listed in sequential order in terms of when they are processed
 * during code flow, in so far as this is possible or applicable.
 *
 * @link https://symfony.com/doc/current/components/event_dispatcher.html#naming-conventions
 */
final class Event {

  const SUITE_LOADED = 'suite.loaded';

  const TEST_CREATED = 'test.created';

  const DRIVER_CREATED = 'driver.created';

  const REQUEST_CREATED = 'request.created';

  const REQUEST_FINISHED = 'request.finished';

  const ASSERT_CREATED = 'assert.created';

  const ASSERT_FINISHED = 'assert.finished';

  const TEST_FINISHED = 'test.finished';

  const SUITE_FINISHED = 'suite.finished';

}
