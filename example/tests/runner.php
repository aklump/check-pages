<?php

/**
 * @file
 * Assert ITLS pages are working.
 */

load_config('config/local');

add_test_option('foo', function($a, $b){
  return;
});

run_suite('options');
run_suite('attributes');
run_suite('suite');
run_suite('suite_dev_only');
