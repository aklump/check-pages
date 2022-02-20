<?php

/**
 * @file
 * A test that will purposeful, failing test suites.
 */
load_config('config/local');
add_directory(__DIR__ . '/suites');

// This shows how we can use globbing.
run_suite('fail_*');
