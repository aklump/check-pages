<?php

/**
 * @file
 * These are functions available for writing tests.
 */

use AKlump\CheckPages\Event\SuiteEventInterface;
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
  global $container;
  $runner = $container->get('runner');

  $input = $container->get('input');
  if ($allow_override && ($override = $input->getOption('config'))) {

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
      $override = $input->getOption('config');
    }

    $config = $override;
  }
  $runner->loadConfig($config);
}

/**
 * Get a config value from the active config.
 *
 * @param $config_path
 *   A dot-separated path for a config key.
 */
function config_get($config_path) {
  global $container;
  $runner = $container->get('runner');
  try {
    $o = new ObjectPath($runner->getConfig());
    if (!$o->exists($config_path)) {
      if (strstr($config_path, $o->getDelimiter()) === FALSE) {
        throw new \RuntimeException(sprintf('No data at path "%s". Are you using the correct path delimiter "%s"?', $config_path, $o->getDelimiter()));
      }
      throw new \RuntimeException(sprintf('The configuration %s does not exist.', $config_path));
    }

    return $o->get($config_path);
  }
  catch (\Exception $exception) {
    throw new RuntimeException(sprintf("%s\nCheck your configuration file: %s", $exception->getMessage(), $runner->getLoadedConfigPath()), $exception->getCode(), $exception);
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
  global $container;
  $runner = $container->get('runner');

  if (substr($absolute_path_to_dir, 0, 1) !== '/') {
    throw new \InvalidArgumentException(sprintf('To add a directory, it must begin with a "/"; "%s" does not.', $absolute_path_to_dir));
  }
  $directory = rtrim(realpath($absolute_path_to_dir), '/');
  if (!$directory || !is_dir($directory)) {
    throw new \InvalidArgumentException("\"$absolute_path_to_dir\" must be an existing directory.");
  }
  $runner->addResolveDirectory($directory);
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
 *   An array keyed by the method from \AKlump\CheckPages\Plugin\LegacyPluginInterface
 *   which should trigger the callback, the value is a callback that will
 *   receive the arguments provided in the test suite yaml file as the value of
 *   $name + the arguments of the given method + an array of contextual info,
 *   including the test running instance.  Basically everything you might need
 *   to customize the runner, suite, test, or assertions.
 *
 * @deprecated This needs to be renamed, i.e. "listen", "create_plugin".
 */
function add_test_option(string $name, array $callbacks) {
  global $container;
  $runner = $container->get('runner');
  $runner->addTestOption($name, $callbacks);
}

/**
 * Subscribe a callback to an event class.
 *
 * @param string|array $event_classname
 *   If string, one of the \Drupal\check_pages\Event\Events constants.
 *   e.g."\Drupal\check_pages\Event\Events::SUITE_LOADED" ; if array
 *   [$event_classname, $priority].
 * @param $callback
 *   The function to call on event.
 *
 * @return $this
 *   Self for chaining.
 *
 * @link https://symfony.com/doc/current/event_dispatcher.html
 */
function respond_to($event, callable $callback) {
  global $container;
  if (!is_array($event)) {
    $event = [$event, 0];
  }
  list($event, $priority) = $event;
  $container->get('runner')
    ->getDispatcher()
    ->addListener($event, $callback, $priority);
}

/**
 * Point to a test suite file to run.
 *
 * @param string $path_to_suite
 *   This can be a resolvable path or a glob pattern, e.g. 'group/suite',
 *   'group/*', 'suite'.
 *
 * @throws \AKlump\CheckPages\Exceptions\SuiteFailedException|\AKlump\CheckPages\Exceptions\TestFailedException
 */
function run_suite(string $path_to_suite, array $suite_config = []): void {
  global $container;
  $runner = $container->get('runner');

  // Process a glob string if present.
  if (strstr($path_to_suite, '*') !== FALSE) {

    if (strstr($path_to_suite, '/') !== FALSE) {
      $directory = dirname($path_to_suite);
      $directory = $runner->resolve($directory);
      $runner->addResolveDirectory($directory);
    }

    $suites = [];
    foreach ($runner->getResolveDirectories() as $resolve_directory) {
      $pattern = $resolve_directory . '/' . $path_to_suite;
      $matches = glob($pattern);
      if ($matches) {
        $suites = array_filter($matches, function ($path) {
          return preg_match('/\.yml$/', $path);
        });
        break;
      }
    }
    if (count($suites) === 0) {
      throw new StopRunnerException(sprintf('The glob "%s" returned no files.', $path_to_suite));
    }
  }
  else {
    $suites = [$path_to_suite];
  }

  foreach ($suites as $suite) {

    // This is a little redundant, but we make sure it's in there.  It will be
    // de-duped by Runner, so no problem calling multiple times.
    $runner->addResolveDirectory(dirname($suite));

    // We also have to make sure the directory above the (group) directory is a
    // resolve directory so we can resolve by group/suite and not just suite.
    // If we did not do this, then you could not resolve with a value of
    // "group/suite".
    $runner->addResolveDirectory(dirname(dirname($suite)));

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
 * @param array $mixin_config
 *   Optional, configuration to be passed to the extra.
 *
 * @throws \AKlump\CheckPages\Exceptions\StopRunnerException If the mixin cannot be found.
 */
function add_mixin(string $mixin, array $mixin_config = []) {
  global $container;
  $runner = $container->get('runner');
  try {
    $require_path = $runner->resolveFile("$mixin.php");
  }
  catch (UnresolvablePathException $exception) {
    $require_path = NULL;
  }
  if (is_null($require_path)) {
    try {
      $require_path = $runner->resolveFile("mixins/$mixin.php");
    }
    catch (\Exception $exception) {
      throw new StopRunnerException(sprintf('Cannot add mixin "%s"; the path does not resolve.', $mixin), $exception->getCode(), $exception);
    }
  }
  try {
    require_once $require_path;
  }
  catch (\Exception $exception) {
    $class = get_class($exception);
    $message = sprintf('The mixin "%s" has failed.%s', $mixin, PHP_EOL . '└── ' . $exception->getMessage());
    throw new $class($message, $exception->getCode(), $exception);
  }
}

/**
 * Register a custom test parameter.
 *
 * This function can be used as syntatic sugar around more complete configs,
 * because it can be used to rewrite an alias, or shorthand--more readable test
 * config--into the necessary configuration needed to run the test.  A common
 * use-case is abstracting API calls.
 *
 * @param string $test_parameter
 *   The key that will be added to the test to indicate this shorthand should be
 *   used called.
 * @param callable $callback
 *   Will receive ($shorthand_value, \AKlump\CheckPages\Parts\Test $test) as
 *   arguments.  It should return void. If mutating the test config, then it
 *   should call $test->setConfig() with the new config.  Or if it's expanding
 *   one to many tests it should use $test->getSuite()->replaceTestWithMultiple().
 *
 * @return void
 */
function add_shorthand(string $test_parameter, callable $callback) {
  global $container;
  $container->get('runner')->addTestOption($test_parameter, [
    'onLoadSuite' => function (SuiteEventInterface $event) use ($callback, $test_parameter) {
      foreach ($event->getSuite()->getTests() as $test) {
        $test_config = $test->getConfig();
        if (isset($test_config[$test_parameter])) {
          $shorthand = $test_config[$test_parameter];
          unset($test_config[$test_parameter]);
          $callback($shorthand, $test);
        }
      }
    },
  ]);
}
