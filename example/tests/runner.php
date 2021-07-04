<?php

/**
 * @file
 * Assert ITLS pages are working.
 */

load_config('config/local');

with_extras('drupal7', [
  'users_json' => __DIR__ . '/config/users.json',
]);

run_suite('login');
run_suite('attributes');
run_suite('suite');
run_suite('suite_dev_only');
