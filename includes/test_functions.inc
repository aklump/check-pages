<?php

/**
 * @file
 * These are functions available for writing tests.
 */

use AKlump\CheckPages\SuiteFailedException;

/**
 * Load the configuration file to use for the test.
 *
 * @param string $config
 *   A resolvable filename to a configuration file.
 * @param bool $allow_override
 *   By default the CLI option --config will override $config.  Set this to
 *   false to block that handling.
 */
function load_config(string $config, bool $allow_override = TRUE) {
  global $app, $cli;

  if ($allow_override && ($override = $cli->getParam('config'))) {
    $config = $override;
  }

  $app->setConfig($config);
}

/**
 * Point to a test suite file to run.
 *
 * @param string $path
 *
 * @return array
 * @throws \AKlump\CheckPages\SuiteFailedException
 */
function run_suite(string $path) {
  global $app;

  try {
    return $app->runSuite($path);
  }
  catch (SuiteFailedException $exception) {
    // TODO Add ability to configure if this stops execution of all tests or not.
    // Purposefully left blank.
  }
}
