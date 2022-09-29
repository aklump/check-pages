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

  const RUNNER_CONFIG_LOADED = 'runner.config_loaded';

  const SUITE_LOADED = 'suite.loaded';

  /**
   * Can be used for fine-tuned validation beyond the json schema.  To
   * invalidate throw a \AKlump\CheckPages\Exceptions\BadSyntaxException.
   */
  const SUITE_VALIDATION = 'suite.validation';

  /**
   * Can be used for fine-tuned validation beyond the json schema.  To
   * invalidate throw a \AKlump\CheckPages\Exceptions\BadSyntaxException.
   */
  const TEST_VALIDATION = 'test.validation';

  const TEST_CREATED = 'test.created';

  const TEST_STARTED = 'test.started';

  const REQUEST_CREATED = 'request.created';

  const REQUEST_FINISHED = 'request.finished';

  const ASSERT_CREATED = 'assert.created';

  const ASSERT_FINISHED = 'assert.finished';

  const TEST_FINISHED = 'test.finished';

  const TEST_FAILED = 'test.failed';

  const TEST_PASSED = 'test.passed';

  const SUITE_FINISHED = 'suite.finished';

  const RUNNER_FINISHED = 'runner.finished';

}
