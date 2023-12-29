<?php

namespace AKlump\CheckPages;

/**
 * Defines the event names dispatched by Check Pages.
 *
 * Constants are not listed in any special order.  See docs for that.
 *
 * @link https://symfony.com/doc/current/components/event_dispatcher.html#naming-conventions
 */
final class Event {

  const RUNNER_CREATED = 'runner.created';

  const RUNNER_STARTED = 'runner.started';

  const SUITE_STARTED = 'suite.loaded';

  /**
   * Can be used for fine-tuned validating beyond the json schema.  To
   * invalidate throw a \AKlump\CheckPages\Exceptions\BadSyntaxException.
   */
  const SUITE_CREATED = 'suite.created';

  /**
   * Can be used for fine-tuned validating beyond the json schema.  To
   * invalidate throw a \AKlump\CheckPages\Exceptions\BadSyntaxException.
   */
  const TEST_CREATED = 'test.created';

  /**
   * @deprecated Use REQUEST_STARTED instead.
   */
  const TEST_STARTED = 'test.started';

  const REQUEST_STARTED = 'request.started';

  const REQUEST_CREATED = 'request.created';

  const REQUEST_PREPARED = 'request.prepared';

  const REQUEST_FINISHED = 'request.finished';

  const ASSERT_CREATED = 'assert.created';

  const ASSERT_FINISHED = 'assert.finished';

  const TEST_FINISHED = 'test.finished';

  const TEST_FAILED = 'test.failed';

  const TEST_PASSED = 'test.passed';

  const TEST_SKIPPED = 'test.skipped';

  const SUITE_FAILED = 'suite.failed';

  const SUITE_PASSED = 'suite.passed';

  const SUITE_SKIPPED = 'suite.skipped';

  const SUITE_FINISHED = 'suite.finished';

  const RUNNER_FINISHED = 'runner.finished';

}
