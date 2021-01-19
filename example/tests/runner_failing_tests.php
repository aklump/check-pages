<?php

/**
 * @file
 * A test that will purposeful, failing test suites.
 */

load_config('config/local');

// This shows how we can use globbing.
run_suite('fail_*');
