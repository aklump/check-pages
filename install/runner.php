<?php

/**
 * @file
 * Assert ITLS pages are working.
 */

load_config('config.live');
run_suite('suite_dev_only');
run_suite('suite');
