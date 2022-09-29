<?php

namespace AKlump\CheckPages\Exceptions;

/**
 * Thrown when add_test_option() callback has failed.
 */
class TestOptionFailed extends \Exception {

  /**
   * @param array $option_context
   *   The context array as passed to the callback of add_test_option().
   * @param $extra_message
   *   An extra message to append to the generated one; for more clarity.
   */
  public function __construct(array $option_context, $extra_message, \Exception $exception = NULL) {
    list($name) = array_keys(($option_context['config'] ?? [])) + ['?'];
    $message = rtrim(sprintf('Option "%s" failed. %s', $name, $extra_message));

    return parent::__construct($message, 0, $exception);
  }

}
