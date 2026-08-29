<?php

namespace AKlump\CheckPages\Messaging;

// TODO Convert this to enum
interface MessageType {

  const NONE = '';

  const CODE = 'code';

  const DEBUG = 'debug';

  const DIVIDER = 'divider';

  const EMERGENCY = 'emergency';

  const WARNING = 'warning';

  const ERROR = 'error';

  const FAILED_ASSERTION = 'failed_assertion';

  const INFO = 'info';

  const PASSED_ASSERTION = 'passed_assertion';

  const SKIPPED = 'skipped';

  const SUCCESS = 'success';

  const TEST_SOURCE = 'test_source';

  const TODO = 'todo';

  const BANNER = 'banner';

  const TITLE = 'title';

  const SUITE_RUN_STATE_RESULT = 'suite.run_state.result';
  const TEST_RUN_STATE_RESULT = 'test.run_state.result';

  const TEST_RUN_STATE_FILES = 'test.run_state.files';
}
