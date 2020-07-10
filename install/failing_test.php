<?php

/**
 * @file
 * Assert ITLS pages are working.
 *
 * Run this test using the following CLI code:
 * @code
 *   ./check suite.php
 * @endcode
 */

load_config('config');
run_suite('fail_response_code');
run_suite('fail_bad_url');
