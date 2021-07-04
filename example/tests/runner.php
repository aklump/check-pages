<?php

/**
 * @file
 * Assert ITLS pages are working.
 */

load_config('config/local');

with_extras('drupal7', [
  'users' => 'config/users.yml',
]);

run_suite('login');
run_suite('attributes');
run_suite('suite');
run_suite('suite_dev_only');
