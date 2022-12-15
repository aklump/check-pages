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

  /**
   * Affect the runner configuration before a suite is run.
   */
  const RUNNER_CONFIG = 'runner.config';

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

  const REQUEST_READY = 'request.ready';

  const REQUEST_FINISHED = 'request.finished';

  const ASSERT_CREATED = 'assert.created';

  const ASSERT_FINISHED = 'assert.finished';

  const REQUEST_TEST_FINISHED = 'request.test.finished';

  const TEST_FAILED = 'test.failed';

  const TEST_PASSED = 'test.passed';

  const TEST_FINISHED = 'test.finished';

  const SUITE_FAILED = 'suite.failed';

  const SUITE_PASSED = 'suite.passed';

  const SUITE_FINISHED = 'suite.finished';

  const RUNNER_FINISHED = 'runner.finished';

}
