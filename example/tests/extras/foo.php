<?php
/**
 * @file Demonstrate the usage of with_extras().
 */
$base_url = config_get('base_url');
assert(is_string($base_url), new \AKlump\CheckPages\Exceptions\StopRunnerException());

