<?php
/**
 * @file Demonstrate the usage of add_mixin().
 */
$base_url = config_get('base_url');
assert(is_string($base_url), new \AKlump\CheckPages\Exceptions\StopRunnerException());

