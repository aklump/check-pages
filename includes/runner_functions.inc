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
 * Add a resolve directory where additional tests may be found.
 *
 * Note, the run_suite('*') pattern does not look in this directory, so you will
 * have to explicitly call the suite name(s).
 *
 * @param string $custom_dir
 *   The absolute path to an existing directory to use for resolution.
 */
function add_directory(string $custom_dir) {
  global $app;
  if (!is_dir($custom_dir)) {
    throw new \InvalidArgumentException("\"$custom_dir\" must be an existing directory.");
  }
  $path = realpath($custom_dir);
  if ($path) {
    $custom_dir = $path;
  }
  $app->addResolveDirectory($custom_dir);
}

function add_test_option(string $name, array $callbacks) {
  global $app;

  $app->addTestOption($name, $callbacks);
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

    $suites = $app->filterSuites($suites);
  }
  else {
    $suites = [$path_to_suite];
  }

  foreach ($suites as $suite) {
    $suite = pathinfo($suite, PATHINFO_FILENAME);
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

function with_extras(string $filename, array $config) {
  ${$filename} = $config;
  require_once "$filename.inc";
}
