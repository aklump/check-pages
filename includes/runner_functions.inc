<?php

/**
 * @file
 * These are functions available for writing tests.
 */

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
 * @param string $path_to_suite
 *   This can be a resolvable path or a glob pattern that will be resolved using
 *   the getPathToSuites() directory, e.g. '*', or 'user*'.
 *
 * @throws \AKlump\CheckPages\SuiteFailedException
 */
function run_suite(string $path_to_suite, array $suite_config = []): void {
  global $app;

  // Process a glob string if present, relative to the --dir or runner.php dir.
  if (strstr($path_to_suite, '*') !== FALSE) {
    $suites = array_filter(glob($app->getPathToSuites() . '/' . $path_to_suite), function ($path) {
      return preg_match('/\.yml$/', $path);
    });
  }
  else {
    $suites = [$path_to_suite];
  }
  foreach ($suites as $suite) {
    $app->runSuite($suite, $suite_config);
  }
}

/**
 * Add a custom command.
 *
 * @param string $name
 *   The name of the command, this will be used when you write your test.
 * @param callable $callback
 *   A callback to execute, with optional parameters.  If you typehint your
 *   parameters this will affect the validation.
 */
function add_command(string $name, callable $callback): void {
  global $app;

  $app->addCommand($name, $callback);
}
