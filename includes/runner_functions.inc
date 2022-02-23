<?php

/**
 * @file
 * These are functions available for writing tests.
 */

use AKlump\CheckPages\Exceptions\StopRunnerException;
use AKlump\CheckPages\Exceptions\UnresolvablePathException;
use MidwestE\ObjectPath;

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
  global $runner, $cli;

  if ($allow_override && ($override = $cli->getParam('config'))) {

    // As a convenience, we will allow --config to omit the standard "config/"
    // path and try to use it if the passed value cannot resolve.  The reason is
    // that it's bothersome to have to type "--config=config/foo", and I wanted
    // to allow "--config=foo" instead.  That's the reason for the next few
    // lines.  July 16, 2021 at 11:23:12 AM PDT, aklump.
    try {
      $runner->resolveFile($override);
    }
    catch (\Exception $exception) {
      $override = 'config/' . $override;
    }
    try {
      $runner->resolveFile($override);
    }
    catch (\Exception $exception) {
      // If the presumption failed, then we just rollback.
      $override = $cli->getParam('config');
    }

    $config = $override;
  }

  $runner->setConfig($config);
}

/**
 * Get a config value from the active config.
 *
 * @param $config_path
 *   A dot-separated path for a config key.
 */
function config_get($config_path) {
  global $runner;
  try {
    $o = new ObjectPath($runner->getConfig());

    return $o->get($config_path);
  }
  catch (\Exception $exception) {
    throw new RuntimeException(sprintf("%s\nCheck your configuration file: %s", $exception->getMessage(), $runner->getPathToConfig()), $exception->getCode(), $exception);
  }
}

/**
 * Add an additional directory to use when resolving paths.
 *
 * Note, the run_suite('*') pattern does not look in this directory, so you will
 * have to explicitly call the suite name(s).
 *
 * @param string $absolute_path_to_dir
 *   The absolute path to an existing directory to use for resolution.  It must
 *   exist and it must be an absolute path.
 */
function add_directory(string $absolute_path_to_dir) {
  global $runner;

  if (substr($absolute_path_to_dir, 0, 1) !== '/') {
    throw new \InvalidArgumentException(sprintf('To add a directory, it must begin with a "/"; "%s" does not.', $absolute_path_to_dir));
  }
  $path = rtrim(realpath($absolute_path_to_dir), '/');
  if (!$path || !is_dir($path)) {
    throw new \InvalidArgumentException("\"$absolute_path_to_dir\" must be an existing directory.");
  }
  $runner->addResolveDirectory($path);
}

/**
 * Add a custom test option and processing.
 *
 * This is a powerful means of customizing this project for a given use case.
 *
 * @param string $name
 *   The name of the test option, which will be used as the key in each test
 *   YAML array.
 * @param array $callbacks
 *   An array keyed by the method from \AKlump\CheckPages\TestPluginInterface
 *   which should trigger the callback, the value is a callback that will
 *   receive the arguments provided in the test suite yaml file as the value of
 *   $name + the arguments of the given method + an array of contextual info,
 *   including the test running instance.  Basically everything you might need
 *   to customize the runner, suite, test, or assertions.
 */
function add_test_option(string $name, array $callbacks) {
  global $runner;
  $runner->addTestOption($name, $callbacks);
}

/**
 * Point to a test suite file to run.
 *
 * @param string $path_to_suite
 *   This can be a resolvable path or a glob pattern that will be resolved using
 *   the getPathToSuites() directory, e.g. '*', or 'user*'.
 *
 * @throws \AKlump\CheckPages\Exceptions\SuiteFailedException|\AKlump\CheckPages\Exceptions\TestFailedException
 */
function run_suite(string $path_to_suite, array $suite_config = []): void {
  global $runner;

  // Process a glob string if present, relative to the --dir or runner.php dir.
  if (strstr($path_to_suite, '*') !== FALSE) {

    if (strstr($path_to_suite, '/') !== FALSE) {
      $directory = dirname($path_to_suite);
      $directory = $runner->resolve($directory);
      $runner->addResolveDirectory($directory);
    }

    $suites = array_filter(glob($runner->getPathToSuites() . '/' . $path_to_suite), function ($path) {
      return preg_match('/\.yml$/', $path);
    });

    $suites = $runner->filterSuites($suites);
  }
  else {
    $suites = [$path_to_suite];
  }

  foreach ($suites as $suite) {
    $suite = pathinfo($suite, PATHINFO_FILENAME);
    $runner->runSuite($suite, $suite_config);
  }
}

/**
 * @param string $extras_filename
 * @param array $config
 *
 * @return void
 * @deprecated Use \add_mixin().
 */
function with_extras(string $extras_filename, array $config) {
  return add_mixin($extras_filename, $config);
}

/**
 * Include an extra in your test runner.
 *
 * Extras are include files that bundle certain functionality, such as logging
 * in to a Drupal website.
 *
 * @param string $extras_filename
 *   The filename without extension of the extras file.  This must be located
 *   adjacent to this file.
 * @param array $config
 *   Configuration to be passed to the extra.
 *
 * @throws \AKlump\CheckPages\Exceptions\StopRunnerException If the mixin cannot be found.
 */
function add_mixin(string $name, array $config) {
  global $runner;
  try {
    $require_path = $runner->resolveFile("$name.php");
  }
  catch (UnresolvablePathException $exception) {
    $require_path = NULL;
  }
  if (is_null($require_path)) {
    try {
      $require_path = $runner->resolveFile("mixins/$name.php");
    }
    catch (\Exception $exception) {
      throw new StopRunnerException(sprintf('Cannot add mixin "%s"; the path does not resolve.', $name), $exception->getCode(), $exception);
    }
  }
  unset($name);
  require_once $require_path;
}
