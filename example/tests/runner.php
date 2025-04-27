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

add_mixin('http_request_files');

run_suite('interpolate/*');
run_suite('on_fail_skip');
run_suite('ignored');
run_suite('secrets');
run_suite('ajax/*');
run_suite('group1/*');
run_suite('group2/*');
run_suite('foo/bar/group3/*');
run_suite('attributes');
run_suite('expected_outcome');
run_suite('last_minute_set_interpolation');

