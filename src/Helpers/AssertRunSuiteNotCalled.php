<?php

namespace AKlump\CheckPages\Helpers;

use AKlump\CheckPages\Parts\Runner;

/**
 * Check that run_suite() has not yet been called.
 */
class AssertRunSuiteNotCalled {

  public function __invoke(Runner $runner, string $function) {
    if ($runner::getState('run_suite_called')) {
      throw new \RuntimeException(sprintf('Calling %s() after calling run_suite() is not allowed.  Fix this by moving the location of %s() in your runner code.', $function, $function));
    }
  }

}
