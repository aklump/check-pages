<?php

/**
 * @file
 * A test that will purposeful, failing test suites.
 */

load_config('config/local');
run_suite('fail_bad_url');
run_suite('fail_response_code');
