<?php

/**
 * @file
 * Assert ITLS pages are working.
 */

load_config('config/local');

with_extras('foo', [
  "title" => "Lorem",
  "color" => "blue",
]);

run_suite('suite');
run_suite('attributes');
run_suite('suite_dev_only');
