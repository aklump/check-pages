<?php

/**
 * @file
 * These are functions available for writing tests.
 */

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Exceptions\StopRunnerException;
use AKlump\CheckPages\Exceptions\UnresolvablePathException;
use AKlump\CheckPages\Files\FilesProviderInterface;
use AKlump\CheckPages\Parts\Suite;
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

  /** @var \AKlump\CheckPages\Files\FilesProviderInterface $files_readonly */
  $files = $container->get('runner')->getFiles();

  $input = $container->get('input');
  if ($allow_override && ($override = $input->getOption('config'))) {

    // As a convenience, we will allow --config to omit the standard "config/"
    // path and try to use it if the passed value cannot resolve.  The reason is
    // that it's bothersome to have to type "--config=config/foo", and I wanted
    // to allow "--config=foo" instead.  That's the reason for the next few
    // lines.  July 16, 2021 at 11:23:12 AM PDT, aklump.
    try {
      $files->tryResolveFile($override, ['yaml', 'yml'])[0];
    }
    catch (\Exception $exception) {
      $override = 'config/' . $override;
    }
    try {
      $files->tryResolveFile($override, ['yaml', 'yml'])[0];
    }
    catch (\Exception $exception) {
      // If the presumption failed, then we just rollback.
      $override = $input->getOption('config');
    }

    $config = $override;
  }
  $container->get('runner')->loadConfig($config);
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
  $container->get('runner')
    ->getFiles()
    ->addResolveDir($absolute_path_to_dir);
}

/**
 * Point to a test suite file to run.
 *
 * @param string $path_to_suite
 *   This can be a resolvable path or a glob pattern, e.g. 'group/suite',
 *   'group/*', 'suite'.  Or a resolvable path like "foo/bar/group/suite".
 *
 * @throws \AKlump\CheckPages\Exceptions\SuiteFailedException|\AKlump\CheckPages\Exceptions\TestFailedException
 */
function run_suite(string $path_to_suite, array $runtime_config = []): void {
  global $container;

  /** @var \AKlump\CheckPages\Parts\Runner $runner */
  $runner = $container->get('runner');

  // Sniff out the group, which the directory holding the suite file, if
  // provided, e.g., "group/suite".  $path_to_suite may contain more than
  // "group/suite", so we will sniff that out too, and add any resolved
  // directories automatically, as appropriate.  Note: the group will be the
  // same, even if globbing creates multiple suites.
  $group = '';
  if (strpos($path_to_suite, '/') !== FALSE) {
    $group = basename(dirname($path_to_suite));
    $resolve_dir = explode($group, $path_to_suite)[0];
    if ($resolve_dir) {
      $resolve_dir = $runner->getFiles()->tryResolveDir($resolve_dir)[0];
      $runner->getFiles()->addResolveDir($resolve_dir);
    }
  }

  // If there was any runtime config added to the single-instance runner last
  // time, we have to remove that.  We do that by reloading the configuration
  // from the original file and then applying any runtime passed at this time.
  $base_config = $runner
    ->loadConfig($runner->getLoadedConfigPath())
    ->getConfig();
  $runtime_config = array_merge($base_config, $runtime_config);
  $runner->setConfig($runtime_config);

  // Expand a suite identified using a glob character
  $has_glob_char = function (string $path) {
    return strstr($path, '*') !== FALSE;
  };
  if ($has_glob_char($path_to_suite)) {
    if (!$has_glob_char(basename($path_to_suite))) {
      throw new StopRunnerException(sprintf('Suite globs are only supported in the basename, check "%s" directory globbing', $path_to_suite));
    }
    // Determine if we have to add the parent directory or not.
    if (strstr($path_to_suite, '/') !== FALSE) {
      $glob_parent_dir = $runner->getFiles()
                           ->tryResolveDir(dirname($path_to_suite))[0];
      $runner->getFiles()->addResolveDir($glob_parent_dir);
    }
    $suite_paths = $runner->getFiles()->tryResolveFile($path_to_suite, [
      'yml',
      'yaml',
    ]);

    if (count($suite_paths) === 0) {
      throw new StopRunnerException(sprintf('The glob "%s" returned no files.', $path_to_suite));
    }
  }
  else {
    $suite_paths = $runner->getFiles()->tryResolveFile($path_to_suite, [
      'yml',
      'yaml',
    ]);
  }

  foreach ($suite_paths as $absolute_path) {

    // This is a little redundant, but we make sure it's in there.  It will be
    // de-duped by Runner, so no problem calling multiple times.
    if ($group && basename(dirname($absolute_path)) === $group) {
      $runner->getFiles()->addResolveDir(dirname($absolute_path));
    }

    $absolute_path = $runner->getFiles()->tryResolveFile($absolute_path, [
      'yaml',
      'yml',
    ])[0];

    $suite_config = Symfony\Component\Yaml\Yaml::parseFile($absolute_path);
    if (!is_array($suite_config)) {
      $suite_config = [];
    }

    $suite_id = pathinfo($absolute_path, PATHINFO_FILENAME);
    $suite = new Suite($suite_id, $suite_config, $runner);
    if ($group) {
      $suite->setGroup($group);
    }

    $runner->run($suite, $absolute_path);
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

  /** @var \AKlump\CheckPages\Files\FilesProviderInterface $files */
  $files = $container->get('runner')->getFiles();
  try {
    $require_path = $files->tryResolveFile($mixin, ['php'])[0];
  }
  catch (UnresolvablePathException $exception) {
    $require_path = NULL;
  }
  if (is_null($require_path)) {
    try {
      $require_path = $files->tryResolveFile("mixins/$mixin", ['php'])[0];
    }
    catch (\Exception $exception) {
      throw new StopRunnerException(sprintf('Cannot add mixin "%s"; the path does not resolve.', $mixin), $exception->getCode(), $exception);
    }
  }
  try {
    // Some variables are available in the require files:
    $runner = $container->get('runner');

    require_once $require_path;
  }
  catch (\Exception $exception) {
    $class = get_class($exception);
    $message = sprintf('The mixin "%s" has failed.%s', $mixin, PHP_EOL . '└── ' . $exception->getMessage());
    throw new $class($message, $exception->getCode(), $exception);
  }
}

/**
 * Add a runtime option with multiple events.
 *
 * This is a powerful means of customizing this project for a given use case.
 *
 * @param string $selector
 *   The name of the test option, which will be used as the key in each test
 *   YAML array.
 * @param array $callbacks
 *   An array keyed by the event name for when the callback is triggered, the
 *   value is a callback that will receive the arguments provided in the test
 *   suite yaml file as the value of $selector + the arguments of the
 *   given method + an array of contextual info, including the test running
 *   instance.  Basically everything you might need to customize the runner,
 *   suite, test, or assertions.
 *
 * @return void
 *
 * @see \AKlump\CheckPages\Event
 */
function add_test_option(string $selector, array $callbacks): void {
  global $container;

  /** @var \AKlump\CheckPages\Parts\Runner $runner */
  $runner = $container->get('runner');
  $runner->tryAddRuntimeOption($selector);

  /** @var $legacy_map Maps old event names to new. */
  $legacy_map = [
    // TODO This has not been written yet. Mar 14, 2023, aklump
    // 'onLoadSuite' => Event::SUITE_STARTED,
    'onBeforeTest' => Event::TEST_CREATED,
    'onBeforeDriver' => Event::TEST_STARTED,
    'onBeforeRequest' => Event::REQUEST_CREATED,
    'onAfterRequest' => Event::REQUEST_FINISHED,
    'onBeforeAssert' => Event::ASSERT_CREATED,
    'onAfterAssert' => Event::ASSERT_FINISHED,
  ];

  $event_class = new ReflectionClass(Event::class);

  foreach ($callbacks as $event_name => $callback) {

    $event_name = $legacy_map[$event_name] ?? $event_name;
    if (!in_array($event_name, $event_class->getConstants())) {
      throw new \InvalidArgumentException(sprintf('The event "%s" is unknown.', $event_name));
    }

    respond_to($event_name, function ($event) use ($callback, $selector, $event_name) {
      $test_option_value = NULL;
      if ($event instanceof TestEventInterface) {
        $test = $event->getTest();
        // This validates that the callback should be called because the
        // name is present in the test configuration.
        if (!$test->has($selector)) {
          return;
        }
        $test_option_value = $test->get($selector);
      }

      if ($event_name == 'onBeforeAssert' && $event instanceof Event\AssertEventInterface) {
        $assert = $event->getAssert();
        $search_value = $assert->options;
        $assert->setSearch('options', $search_value);
      }

      $callback($test_option_value, $event);
    });
  }
}

/**
 * Add a runtime option to be executed against a suite.
 *
 * This function can be used as syntatic sugar around more complete configs,
 * because it can be used to rewrite an alias, or shorthand--more readable test
 * config--into the necessary configuration needed to run the test.  A common
 * use-case is abstracting API calls.
 *
 * @param string $selector
 *   The key that will be added to the test to indicate this shorthand callback
 *   should be used.
 * @param callable $callback
 *   Will receive ($selector, \AKlump\CheckPages\Parts\Test $test) as
 *   arguments.  It should return void. If mutating the test config, then it
 *   should call $test->setConfig() with the new config.  Or if it's expanding
 *   one to many tests it should use $test->getSuite()->replaceTestWithMultiple().
 *
 * @return void
 */
function add_shorthand(string $selector, callable $callback) {
  global $container;
  $container->get('runner')->tryAddRuntimeOption($selector);

  respond_to(Event::SUITE_STARTED, function ($event) use ($callback, $selector) {
    foreach ($event->getSuite()->getTests() as $test) {
      if (!$test->has($selector)) {
        continue;
      }
      $shorthand = $test->get($selector);
      $test_config = $test->getConfig();
      unset($test_config[$selector]);
      $test->setConfig($test_config);
      $callback($shorthand, $test);
    }
  });
}

/**
 * Subscribe a callback to an event class.
 *
 * @param string|array $event_name
 *   If string, one of the \Drupal\check_pages\Event\Events constants.
 *   e.g."\Drupal\check_pages\Event\Events::SUITE_STARTED" ; if array
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
