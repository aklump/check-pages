<?php

/**
 * @file
 * Assert ITLS pages are working.
 */

add_directory(__DIR__ . '/suites');
load_config('config/local');

add_mixin('mixins/foo', [
  "title" => "Lorem",
  "color" => "blue",
]);

run_suite('group1/*');
run_suite('group2/*');
run_suite('suite');
run_suite('attributes');
run_suite('suite_dev_only');

