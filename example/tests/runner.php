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

add_mixin('phpstorm.http');

run_suite('ignored');
run_suite('group1/*');
run_suite('group2/*');
run_suite('foo/bar/group3/*');
run_suite('attributes');
run_suite('expected_outcome');

