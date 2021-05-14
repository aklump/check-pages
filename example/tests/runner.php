<?php

/**
 * @file
 * Assert ITLS pages are working.
 */

load_config('config/local');
run_suite('attributes');
run_suite('suite');
run_suite('suite_dev_only');
