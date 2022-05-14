<?php

namespace AKlump\CheckPages\Parts;

use AKlump\CheckPages\Assert;
use AKlump\CheckPages\ChromeDriver;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\AssertEvent;
use AKlump\CheckPages\Event\DriverEvent;
use AKlump\CheckPages\Event\RunnerEvent;
use AKlump\CheckPages\Event\SuiteEvent;
use AKlump\CheckPages\Event\TestEvent;
use AKlump\CheckPages\Exceptions\StopRunnerException;
use AKlump\CheckPages\Exceptions\SuiteFailedException;
use AKlump\CheckPages\Exceptions\TestFailedException;
use AKlump\CheckPages\Exceptions\UnresolvablePathException;
use AKlump\CheckPages\Files;
use AKlump\CheckPages\GuzzleDriver;
use AKlump\CheckPages\Output\Debugging;
use AKlump\CheckPages\Output\Feedback;
use AKlump\CheckPages\Output\SourceCodeOutput;
use AKlump\CheckPages\Plugin\PluginsManager;
use AKlump\CheckPages\RequestDriverInterface;
use AKlump\CheckPages\SerializationTrait;
use AKlump\CheckPages\Storage;
use AKlump\CheckPages\StorageInterface;
use AKlump\LoftLib\Bash\Color;
use GuzzleHttp\Exception\ServerException;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Yaml\Yaml;

class Runner {

  use SerializationTrait;
  use SetTrait;

  /**
   * The filename without extension or path.
   *
   * @var string
   */
  const SCHEMA_VISIT = 'schema.suite.DO_NOT_EDIT';

  const OUTPUT_NORMAL = 1;

  const OUTPUT_QUIET = 2;

  const OUTPUT_DEBUG = 3;

  protected $outputMode;

  protected $totalTestsRun = 0;

  protected $failedTestCount = 0;

  protected $failedSuiteCount = 0;

  protected $totalAssertions = 0;

  protected $failedAssertionCount = 0;

  protected $storage;

  protected $schema;

  /**
   * Keys are filter types; values are arrays of filter values for that type.
   *
   * @var array
   */
  protected $filters = [];

  /**
   * @var array
   */
  protected $resolvePaths = [];

  /**
   * @var array
   */
  protected $printed = [];

  /**
   * True if in debug mode.
   *
   * @var bool
   */
  protected $debugging = FALSE;

  /**
   * An array of debug messages.
   *
   * @var array
   */
  protected $messages = [];

  /**
   * @var \AKlump\LoftLib\Bash\Bash
   */
  protected $bash;

  /**
   * @var string
   */
  protected $rootDir;

  /**
   * @var string
   */
  protected $configPath;

  /**
   * @var array
   */
  protected $config = [];

  /**
   * @var array
   */
  protected $options = [];

  /**
   * @var array
   */
  protected $writeToFileResources;

  /**
   * @var \AKlump\CheckPages\Plugin\PluginsManager
   */
  protected $pluginsManager;

  /**
   * @var array
   */
  protected $runner;

  /**
   * @var \AKlump\CheckPages\Parts\Suite
   */
  protected $suite;

  /**
   * @var \AKlump\CheckPages\Output\Debugging
   */
  protected $debugger;

  /**
   * Holds a true state only when any filter is set and after a suite matching
   * the filter is used.  If a filter is set and all suites are run and this is
   * still FALSE, it means that the filter was for a suite that was not
   * registered in the runner.
   *
   * @var bool
   */
  private $filtersWereApplied = FALSE;

  /**
   * Cache of discovered filepath.
   *
   * @var string
   */
  private $pathToFiles;

  private $input;

  private $output;

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  private $dispatcher;

  /**
   * App constructor.
   *
   * @param string $root_dir
   *   The system path to this test app root directory.  The schema files, for
   *   example are found in this directory.
   */
  public function __construct(string $root_dir, InputInterface $input, OutputInterface $output) {
    $this->input = $input;
    $this->output = $output;
    $this->debugger = new Debugging($output);

    $this->rootDir = $root_dir;
    $this->addResolveDirectory($this->rootDir);
    if (is_dir($this->rootDir . '/tests')) {
      $this->addResolveDirectory($this->rootDir . '/tests');
    }
    $this->pluginsManager = new PluginsManager($this, $this->rootDir . '/plugins');
    $this->schema = [];
    $schema_path = $this->rootDir . '/' . static::SCHEMA_VISIT . '.json';
    if (file_exists($schema_path)) {
      $this->schema = json_decode(file_get_contents($schema_path), TRUE);
    }
    $this->pluginsManager->setSchema($this->schema);
  }

  /**
   * @return int
   */
  public function getTotalTestsRun(): int {
    return $this->totalTestsRun;
  }

  /**
   * @return int
   */
  public function getTotalPassingTestsRun(): int {
    return $this->totalTestsRun - $this->failedTestCount;
  }

  /**
   * @return int
   */
  public function getTotalFailedTests(): int {
    return $this->failedTestCount;
  }

  /**
   * @return int
   */
  public function getTotalFailedAssertions(): int {
    return $this->failedAssertionCount;
  }

  /**
   * @return int
   */
  public function getTotalAssertionsRun(): int {
    return $this->totalAssertions;
  }

  public function getInput(): InputInterface {
    return $this->input;
  }

  /**
   * Get the output method.
   *
   * @return \Symfony\Component\Console\Output\OutputInterface
   *   The instance to use for output.
   */
  public function getOutput(): OutputInterface {
    return $this->output;
  }

  /**
   * @return \Symfony\Component\EventDispatcher\EventDispatcher
   */
  public function getDispatcher(): EventDispatcher {
    if (empty($this->dispatcher)) {
      $this->dispatcher = new EventDispatcher();
      global $container;
      if ($container) {
        $serviceIds = $container->findTaggedServiceIds('event_subscriber');
        foreach ($serviceIds as $serviceId => $tags) {
          $listeners = $container->get($serviceId)->getSubscribedEvents();

          $listeners = array_map(function ($listener) {
            $priority = 0;
            if (isset($listener[1]) && is_numeric($listener[1])) {
              $priority = $listener[1];
            }
            if (is_callable($listener)) {
              return [[$listener, $priority]];
            }
            elseif (is_callable($listener[0])) {
              return [[$listener[0], $priority]];
            }
          }, $listeners);

          foreach ($listeners as $event_name => $callbacks) {
            foreach ($callbacks as $callback_set) {
              list($callback, $priority) = $callback_set;
              $this->dispatcher->addListener($event_name, $callback, $priority);
            }
          }
        }
      }
    }

    return $this->dispatcher;
  }

  /**
   * Set the runner information.
   *
   * @param string $basename
   *   The basename of the runner.
   * @param array $options
   *   The runner options.
   *
   * @return $this
   *   Self for chaining.
   */
  public function setBasename(string $basename): Runner {
    if (strstr($basename, '/') !== FALSE) {
      throw new \InvalidArgumentException(sprintf('::setRunner() only takes a basename, not a full path; change "%s" to "%s"', $basename, basename($basename)));
    }
    $this->runner = [
      'name' => $basename,
    ];

    // TODO This needs to be reworked.
    $this->debugging = !$this->getOutput()->isQuiet();

    $this->outputMode = Runner::OUTPUT_NORMAL;
    if ($this->getOutput()->isQuiet()) {
      $this->outputMode = Runner::OUTPUT_QUIET;
    }

    $this->sourceCode = new SourceCodeOutput($this);

    return $this;
  }

  /**
   * @return string
   *   The path to the currently loaded config.
   */
  public function getLoadedConfigPath(): string {
    return $this->configPath;
  }

  public function loadConfig(string $resolve_config_path) {
    $config_path = $this->resolveFile($resolve_config_path);
    $config = Yaml::parseFile($config_path);
    if ($config) {
      $this->setConfig($config);
      $this->configPath = $config_path;
      $this->getDispatcher()
        ->dispatch(new RunnerEvent($this), Event::RUNNER_CONFIG_LOADED);
    }

    return $this;
  }

  /**
   * Set the config file.
   *
   * @param string $path
   *   A resolvable path to the config file.
   *
   * @return \AKlump\CheckPages\Parts\Runner
   *   Self for chaining.
   *
   * @see load_config()
   */
  public function setConfig(array $config): Runner {
    $this->config = $config;

    return $this;
  }

  /**
   * Return the active configuration values.
   *
   * @return array
   *   The configuration array.
   *
   * @see ::getInput()->getOptions() for CLI options.
   */
  public function getConfig(): array {
    return $this->config;
  }

  /**
   * Add a suite name to filter against.
   *
   * You may call this more than once to have more than one suite.
   *
   * @param string $filter
   *   The suite name.
   *
   * @return $this
   */
  public function addSuiteIdFilter(string $filter): Runner {
    $pathinfo = pathinfo($filter);
    if (!empty($pathinfo['extension'])) {
      throw new \InvalidArgumentException(sprintf('Omit the file extension for filter values; use "%s" not "%s".', $pathinfo['filename'], $pathinfo['basename']));
    }
    $this->addFilterByType('id', $filter);

    return $this;
  }

  public function addSuiteGroupFilter(string $filter): Runner {
    $this->addFilterByType('group', $filter);

    return $this;
  }

  /**
   * Add a filter by type and value.
   *
   * @param string $type
   *   E.g. one of 'suite', 'group'.
   * @param string $value
   *
   * @return void
   */
  private function addFilterByType(string $type, string $value) {
    if (strstr($value, ',')) {
      throw new \InvalidArgumentException(sprintf('$value may not contain a comma; the value %s is invalid.', $value));
    }
    if (!isset($this->filters[$type]) || !in_array($value, $this->filters[$type])) {
      $this->filters[$type][] = $value;
    }
  }

  /**
   * Apply all filter types on an array of suites.
   *
   * @param \AKlump\CheckPages\Parts\Suite[]
   *   An array of suites to be filtered by all active filters.
   *
   * @return \AKlump\CheckPages\Parts\Suite[]
   *   Any suites that match all filters; if no filters, then all suites.
   */
  private function applyFilters(array $suites): array {
    if (!count($this->filters)) {
      return $suites;
    }
    $filtered_values = array_filter($suites, function (Suite $suite) {
      foreach ($this->filters as $type => $values) {
        foreach ($values as $value) {
          switch ($type) {
            case 'group':
              if ($value != $suite->getGroup()) {
                return FALSE;
              }
              break;

            case 'id':
              if ($value != $suite->id()) {
                return FALSE;
              }
              break;
          }
        }
      }

      return TRUE;
    });
    if (count($filtered_values)) {
      $this->filtersWereApplied = TRUE;
    }

    return $filtered_values;
  }

  public function getTestOptions(): array {
    return $this->options;
  }

  /**
   * @return \AKlump\CheckPages\Parts\Suite|null
   */
  public function getSuite() {
    return $this->suite ?? NULL;
  }

  /**
   * Add a custom command.
   *
   * @param string $name
   *   The unique command name.
   * @param array $callbacks
   *
   * @return \AKlump\CheckPages\Parts\Runner
   *   Self for chaining.
   */
  public function addTestOption(string $name, array $callbacks): self {
    $this->options[$name] = ['name' => $name, 'hooks' => []];
    foreach ($callbacks as $hook => $callback) {
      $callback_reflection = new \ReflectionFunction($callback);
      $arguments = array_map(function ($param) {
        $type = $param->getType();

        return [
          $type ? $type->getName() : '',
          $param->getName(),
        ];
      }, $callback_reflection->getParameters());
      $this->options[$name]['hooks'][$hook] = [
        'name' => $hook,
        'arguments' => $arguments,
        'callback' => $callback,
      ];
    }

    return $this;
  }

  /**
   * @param string $path
   *   An absolute path to a directory to be used for resolving paths.
   *
   * @return \AKlump\CheckPages\Parts\Runner
   *   Self for chaining.
   */
  public function addResolveDirectory(string $path): self {
    $path = rtrim($path, '/');
    if (!in_array($path, $this->resolvePaths)) {
      $this->resolvePaths[] = $path;
    }

    return $this;
  }

  public function getResolveDirectories(): array {
    return $this->resolvePaths;
  }

  /**
   * Get the resolved test filepath.
   *
   * @return string
   *   The resolved test filepath.
   */
  public function getRunnerPath(): string {
    try {
      return $this->resolveFile((string) $this->runner['name']);
    }
    catch (\Exception $exception) {
      return '';
    }
  }

  /**
   * Run a test file.
   *
   * @throws \RuntimeException If the test completed with failures.
   * @throws \AKlump\CheckPages\Exceptions\SuiteFailedException If the runner stopped
   *   before it was finished due to a failure.
   * @throws \AKlump\CheckPages\Exceptions\TestFailedException If the runner stopped
   *   before it was finished due to a failure.
   */
  public function executeRunner() {
    try {
      $runner_path = $this->getRunnerPath();

      $filter_message = '';
      if (count($this->filters)) {
        $filter_message = '?' . urldecode(http_build_query($this->filters));
        $filter_message = preg_replace('/\[\d+\]/', '[]', $filter_message);
      }
      $this->getOutput()
        ->writeln(Color::wrap('blue', sprintf('Testing started with %s%s', basename($runner_path), $filter_message)));

      require $runner_path;

      if (count($this->filters) > 0 && !$this->filtersWereApplied) {
        if ($this->getOutput()->isDebug()) {
          throw new \RuntimeException(sprintf('There are no suites in %s that match at least one of your filters.  This can happen if you have not added `run_suite()` with a path to the intended suite(s).', basename($runner_path)));
        }
      }

    }
    catch (StopRunnerException $exception) {
      $this->getDispatcher()
        ->dispatch(new RunnerEvent($this), Event::RUNNER_FINISHED);
      throw $exception;
    }
    $this->getDispatcher()
      ->dispatch(new RunnerEvent($this), Event::RUNNER_FINISHED);

    if ($this->failedTestCount) {
      throw new \RuntimeException();
    }
  }

  /**
   * Visit an URLs definition found in $path.
   *
   * @param string $path
   *   A resolvable path to a yaml file.
   *
   * @throws \AKlump\CheckPages\Exceptions\TestFailedException
   * @throws \AKlump\CheckPages\Exceptions\SuiteFailedException
   * @see run_suite()
   *
   */
  public function runSuite(string $path_to_suite, array $suite_config = []) {
    $resolved_path = '';
    $path_to_suite = $this->resolveFile($path_to_suite, $resolved_path);
    $suite_id = pathinfo(substr($path_to_suite, strlen($resolved_path) + 1), PATHINFO_FILENAME);

    // The runner has config in YAML, which the suite uses by default, however
    // we allow per-suite overrides as well.
    $suite_config = array_merge($this->getConfig(), $suite_config);
    $suite = new Suite($suite_id, $suite_config, $this);
    unset($suite_config);

    $this->suite = $suite->setGroup(basename(dirname($path_to_suite)));
    $this->validateSuiteConfigAgainstSchema($suite);

    $ignored_suite_paths = array_filter(array_map(function ($suite_to_ignore) {
      try {
        return $this->resolveFile($suite_to_ignore);
      }
      catch (UnresolvablePathException $exception) {

        // If we're asked to ignore a suite that can't be resolved, then that is
        // not an exception in this case, we can easily ignore it because we
        // can't find it.  Return NULL, which will be filtered out.
        return NULL;
      }
    }, $suite->getConfig()['suites_to_ignore'] ?? []));

    if (!$this->applyFilters([$this->suite])) {
      return;
    }

    Feedback::$suiteTitle = $this->getOutput()->section();

    if (in_array($path_to_suite, $ignored_suite_paths)) {
      $message = Color::wrap('blue', '😴 ' . sprintf('Skipping "%s".', $suite));
      $this->getOutput()
        ->writeln($message, OutputInterface::VERBOSITY_DEBUG);

      return;
    }

    // Add the tests to the suite in prep for the plugin hook...
    $suite_yaml = file_get_contents($path_to_suite);

    // If a suite file is empty, then just ignore it.  It's probably a stub file.
    if (empty(trim($suite_yaml))) {
      return;
    }

    $data = Yaml::parse($suite_yaml);
    foreach ($data as $config) {
      $suite->addTest($config);
    }

    $this->dispatcher->dispatch(new SuiteEvent($suite), Event::SUITE_LOADED);

    // On return from the hook, we need to reparse to get format for validation.
    $suite_yaml = Yaml::dump($suite->jsonSerialize());
    $data = Yaml::parse($suite_yaml, YAML::PARSE_OBJECT_FOR_MAP);
    $this->validateSuiteYaml($data, static::SCHEMA_VISIT . '.json');

    if (!$this->debugging && empty($this->printed['base_url'])) {
      echo Color::wrap('white on blue', sprintf('Base URL is %s', $this->getConfig()['base_url'])) . PHP_EOL;
      $this->printed['base_url'] = TRUE;
    }

    $title = sprintf('Running %s%s suite...', ltrim($suite->getGroup() . '/', '/'), $suite->id());
    Feedback::updateSuiteTitle($this->getOutput(), $title);

    $this->messages = [];
    $failed_tests = 0;

    foreach ($suite->getTests() as $test) {

      // This decides the render order.
      Feedback::$testTitle = $this->getOutput()->section();
      Feedback::$requestUrl = $this->getOutput()->section();
      Feedback::$requestHeaders = $this->getOutput()->section();
      Feedback::$requestBody = $this->getOutput()->section();
      Feedback::$responseHeaders = $this->getOutput()->section();
      Feedback::$responseBody = $this->getOutput()->section();
      Feedback::$testDetails = $this->getOutput()->section();
      Feedback::$testResult = $this->getOutput()->section();

      //
      // Interpolate the test as the variables may have changed.
      //
      if (count($suite->variables())) {
        $config = $test->getConfig();
        foreach (array_keys($config) as $key) {

          // We MUST NOT INTERPOLATE `find` at this time, that will take place
          // inside of the doFindAssert method.  That is because variables can
          // be set on every assert, and if you interpolate too soon, you may
          // replace with the incorrect values.  However the rest of the config
          // should be interpolated, such as will affect the URL.
          if ($key !== 'find') {
            $config[$key] = $suite->variables()
              ->interpolate($config[$key]);
          }
        }
        $test->setConfig($config);
      }
      $this->dispatcher->dispatch(new TestEvent($test), Event::TEST_CREATED);

      // It's possible the test was already run during Event::TEST_CREATED, if
      // that handle has set the results, then the test should be considered
      // complete.
      if (!$test->hasFailed() && !$test->hasPassed()) {
        $this->runTest($test);
      }

      // Decide if we should stop the runner or not.
      if ($test->hasFailed()) {

        // TODO echo the reason here?

        $this->failedTestCount++;
        $failed_tests++;
        if ($this->getConfig()['stop_on_failed_test'] ?? FALSE) {
          throw new TestFailedException($test->getConfig());
        }
      }
    }


    $title = sprintf('%s suite', ltrim($suite->getGroup() . '/', '/') . $suite->id());
    Feedback::updateSuiteTitle($this->getOutput(), $title, !boolval($failed_tests));

    $this->dispatcher->dispatch(new SuiteEvent($suite), Event::SUITE_FINISHED);

    if ($failed_tests) {
      $this->failedSuiteCount++;
      if ($this->getConfig()['stop_on_failed_suite'] ?? FALSE) {
        throw new SuiteFailedException($suite->id());
      }
    }
  }

  public function getMessageOutput(): string {
    $color_map = [
      'error' => 'red',
      'info' => 'blue',
      'success' => 'green',
      'debug' => 'dark gray',
    ];
    $messages = array_map(function ($item) use ($color_map) {
      return Color::wrap($color_map[$item['level']], $item['data']);
    }, $this->messages);
    $output = implode(PHP_EOL, $messages);
    $this->messages = [];

    return $output;
  }

  /**
   * Handle visitation to a single URL.
   *
   * @param array $config
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  protected function runTest(Test $test): void {

    // Ensure find is an array so we don't have to check below in two places.
    $config = $test->getConfig();
    if (empty($config['find']) || !is_array($config['find'])) {
      $config['find'] = empty($config['find']) ? [] : [$config['find']];
    }
    $test->setConfig($config);

    $this->dispatcher->dispatch(new TestEvent($test), Event::TEST_STARTED);

    $this->totalTestsRun++;
    $test_passed = function (bool $result = NULL): bool {
      static $state;
      if (!is_null($result)) {
        $state = is_null($state) || $state ? $result : FALSE;
      }

      return boolval($state);
    };

    $driver = new GuzzleDriver();
    $is_http_test = !empty($config['url']);
    if ($is_http_test) {
      $config = $test->getConfig();

      $debug = $config;
      $debug['find'] = '';
      $this->debugger->echoYaml($debug, 0, function ($yaml) {
        // To make the output cleaner we need to remove the printed '' since find
        // is really an array, whose elements are yet to be printed.
        return str_replace("find: ''", 'find:', $yaml);
      });

      if ($config['js'] ?? FALSE) {
        try {
          if (empty($this->getConfig()['chrome'])) {
            throw new \InvalidArgumentException(sprintf("Javascript testing is unavailable due to missing path to Chrome binary.  Add \"chrome\" in file %s.", $this->getLoadedConfigPath()));
          }
          $driver = new ChromeDriver($this->getConfig()['chrome']);
        }
        catch (\Exception $exception) {
          throw new TestFailedException($config, $exception);
        }
      }

      $this->dispatcher->dispatch(new DriverEvent($test, $driver), Event::REQUEST_CREATED);

      try {
        $response = $driver
          ->setUrl($this->url($test->getConfig()['url']))
          ->request()
          ->getResponse();
      }
      catch (ServerException $exception) {
        $response = $exception->getResponse();
      }

      $http_location = NULL;

      // If not specified, then any 2XX will pass.
      $http_response_code = $response->getStatusCode();
      if (empty($test->getConfig()['expect'])) {
        $test_passed($http_response_code >= 200 && $http_response_code <= 299);
      }
      else {
        if ($test->getConfig()['expect'] >= 300 && $test->getConfig()['expect'] <= 399) {
          $http_location = $driver->getLocation();
          $http_response_code = $driver->getRedirectCode();
        }

        $test_passed($http_response_code == $test->getConfig()['expect']);
      }

      if (!$test_passed()) {
        $test->setFailed();
      }
      $this->dispatcher->dispatch(new DriverEvent($test, $driver), Event::REQUEST_FINISHED);

      if ($test_passed()) {
        $this->pass('├── HTTP ' . $http_response_code);
      }
      else {
        $this->failReason(sprintf("├── Expected HTTP %s, got %d", $test->getConfig()['expect'] ?? '2xx', $http_response_code));
      }

      // Test the location if asked.
      $expected_location = $test->getConfig()['location'] ?? '';
      if (empty($expected_location)) {
        $expected_location = $test->getConfig()['redirect'] ?? '';
      }
      if ($http_location && $expected_location) {
        $location_test = $http_location === $this->url($expected_location);
        $test_passed($location_test);
        if (!$location_test) {
          $this->failReason(sprintf('├── The actual location: %s did not match the expected location: %s', $http_location, $this->url($expected_location)));
        }
      }
    }

    $assertions = $test->getConfig()['find'] ?? [];
    if (count($assertions) === 0) {
      $test_passed(TRUE);
      if ($this->getOutput()->isDebug()) {
        Feedback::$testDetails->write(Color::wrap('light gray', '├── This test has no assertions.'));
      }
    }
    else {
      $id = 0;
      $this->debugger->lineBreak();
      while ($definition = array_shift($assertions)) {
        if (is_scalar($definition)) {
          $definition = [Assert::ASSERT_CONTAINS => $definition];
        }

        $assert = $this->doFindAssert($test, strval($id), $definition, $driver);

        $test_passed($assert->getResult());

        ++$id;
      }
    }

    $test->setResults($this->messages);

    if ($test_passed()) {
      $test->setPassed();
    }
    else {
      $test->setFailed();
    }

    $this->dispatcher->dispatch(new DriverEvent($test, $driver), Event::TEST_FINISHED);
  }

  /**
   * Resolve a path.
   *
   * @param string $path
   *   This can be a resolvable path or an absolute path; if it's an absolute
   *   path, it will simply be returned.
   * @param string &$resolved_path
   *   This variable will be set with the parents used to resolve $path.
   * @param array $extensions
   *   One or more extensions you're looking for.
   *
   * @return string
   *   The resolved full path to a file if it exists.
   *
   * @throws \AKlump\CheckPages\Exceptions\UnresolvablePathException
   *   If the path cannot be resolved.
   */
  public function resolveFile(string $path, string &$resolved_path = '', array $extensions = [
    'yml',
    'yaml',
  ]
  ) {
    if (substr($path, 0, 1) === '/' && file_exists($path)) {
      $resolved_path = dirname($path);

      return $path;
    }

    // If $path has an extension it will trump $extensions.
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    if ($ext) {
      $path = substr($path, 0, -1 * (strlen($ext) + 1));
      $extensions = [$ext];
    }

    $candidates = [['', $path]];
    foreach ($this->resolvePaths as $resolve_path) {
      $resolve_path = rtrim($resolve_path, '/');
      $candidates[] = [$resolve_path, $resolve_path . '/' . $path];
      foreach ($extensions as $extension) {
        $candidates[] = [
          $resolve_path,
          $resolve_path . '/' . $path . '.' . trim($extension, '.'),
        ];
      }
    }
    while (($try = array_shift($candidates))) {
      list($resolved_path, $try) = $try;
      if (is_file($try)) {
        return $try;
      }
    }
    throw new UnresolvablePathException($path);
  }

  /**
   * Resolve a path.
   *
   * @param string $path
   * @param string &$resolved_path
   *   This variable will be set with the parents used to resolve $path.
   *
   * @return string
   *   The resolved full path to a file if it exists.
   *
   * @throws \AKlump\CheckPages\Exceptions\UnresolvablePathException
   *   If the path cannot be resolved.
   */
  public function resolve(string $path, &$resolved_path = NULL) {
    $candidates = [['', $path]];
    foreach ($this->resolvePaths as $resolve_path) {
      $resolve_path = rtrim($resolve_path, '/');
      $candidates[] = [$resolve_path, $resolve_path . '/' . $path];
    }
    while (($try = array_shift($candidates))) {
      list($resolved_path, $try) = $try;
      if (file_exists($try)) {
        return $try;
      }
    }
    throw new UnresolvablePathException($path);
  }

  /**
   * Resolve a relative URL to the configured base_url.
   *
   * If the url does not being with a '/', it will be assumed it is already
   * resolved and the value will pass through.
   *
   * @param string $possible_relative_url
   *   THe relative URL, beginning with an '/'.
   *
   * @return string
   *   The absolute URL.
   */
  public function url(string $possible_relative_url): string {
    if (substr($possible_relative_url, 0, 1) !== '/') {
      return $possible_relative_url;
    }

    return rtrim($this->getConfig()['base_url'], '/') . '/' . trim($possible_relative_url, '/');
  }

  /**
   * Load YAML from a file first validating against the schema.
   *
   * @param string $path
   * @param string $schema_basename
   *
   * @return array
   */
  protected function validateSuiteYaml($data, string $schema_basename): array {

    // Do not validate $data properties that have been added using add_test_option().
    $path_to_schema = $this->rootDir . '/' . $schema_basename;
    $schema = json_decode(file_get_contents($path_to_schema));

    // Dynamically add permissive items for every test option.  These have been
    // added on the fly and do not have schema support, as plugins do.
    foreach ($this->getTestOptions() as $test_option) {
      $schema->items->anyOf[] = (object) [
        'required' => [$test_option['name']],
        'additionalProperties' => TRUE,
      ];
    }

    $validator = new Validator();
    $validator->validate($data, $schema);
    if (!$validator->isValid()) {

      if ($this->getOutput()->isDebug()) {
        echo Color::wrap('white on red', "Suite Group\\ID:") . PHP_EOL;
        echo Color::wrap('light gray', strval($this->getSuite())) . PHP_EOL;
        echo PHP_EOL;
        echo Color::wrap('white on red', "Test Configuration:") . PHP_EOL;
        echo Color::wrap('light gray', json_encode($data, JSON_PRETTY_PRINT)) . PHP_EOL;
        echo PHP_EOL;
        echo Color::wrap('white on red', "Schema Path:") . PHP_EOL;
        echo Color::wrap('light gray', $path_to_schema) . PHP_EOL;
        echo PHP_EOL;
        echo Color::wrap('white on red', "Schema Validation Errors:") . PHP_EOL;
        foreach ($validator->getErrors() as $error) {
          echo Color::wrap('light gray', sprintf("[%s] %s", $error['property'], $error['message'])) . PHP_EOL;
        }
      }

      throw new \RuntimeException(sprintf('The test does not match schema "%s". Use -vvv for more info.', $schema_basename));
    }

    // Convert to arrays, we only needed objects for the validation.
    return json_decode(json_encode($data), TRUE);
  }

  /**
   * Validate a configuration array against the configuration schema.
   *
   * @param \AKlump\CheckPages\Parts\Suite $suite
   *
   * @throws \Exception
   */
  protected function validateSuiteConfigAgainstSchema(Suite $suite) {
    // Convert to objects.
    $config = json_decode(json_encode($suite->getConfig()));
    $validator = new Validator();
    try {
      $validator->validate($config, (object) ['$ref' => 'file://' . $this->rootDir . '/' . 'schema.config.json'], Constraint::CHECK_MODE_EXCEPTIONS);
    }
    catch (\Exception $exception) {
      // Add in the file context.
      $class = get_class($exception);
      throw new $class(sprintf('In configuration : %s', strtolower($exception->getMessage())), $exception->getCode(), $exception);
    }

    // Convert to arrays, we only needed objects for the validation.
    $config = json_decode(json_encode($config), TRUE);
    $suite->setConfig($config);
  }

  /**
   * Apply a single find action in the text.
   *
   * @param string $id
   *   An arbitrary value to track this assert by outside consumers.
   * @param array $definition
   *   The definition of the assertion, e.g. ['dom' => '#logo', 'count' => 1].
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response containing the custom headers and body.
   *
   * @return \AKlump\CheckPages\Assert
   *    The result can be read from getResult().
   */
  protected function doFindAssert(Test $test, string $id, array $definition, RequestDriverInterface $driver): Assert {
    $response = $driver->getResponse();
    $definition = $this->getSuite()->variables()->interpolate($definition);

    $this->debugger->echoYaml($definition, 2);

    $assert = new Assert($definition, $id);
    $assert
      ->setSearch(Assert::SEARCH_ALL)
      ->setHaystack([strval($response->getBody())]);

    if (!empty($definition[Assert::MODIFIER_ATTRIBUTE])) {
      $assert->setModifer(Assert::MODIFIER_ATTRIBUTE, $definition[Assert::MODIFIER_ATTRIBUTE]);
    }

    if ($assert->set) {
      // This may be overridden below if there is more going on than just `set`,
      // and that's totally fine and the way it should be.  However if only
      // setting, we need to know that later own in the flow.
      $assert->setAssertion(Assert::ASSERT_SETTER, NULL);
    }

    $assertions = array_map(function ($help) {
      return $help->code();
    }, $assert->getAssertionsInfo());
    foreach ($assertions as $code) {
      if (isset($definition[$code])) {
        $assert->setAssertion($code, $definition[$code]);
        break;
      }
    }

    $this->dispatcher->dispatch(new AssertEvent($assert, $test, $driver), Event::ASSERT_CREATED);
    $assert->run();
    $this->dispatcher->dispatch(new AssertEvent($assert, $test, $driver), Event::ASSERT_FINISHED);

    if ($assert->set) {
      $message = $this->setKeyValuePair($test->getSuite()
        ->variables(), $assert->set, $assert->getNeedle());
      if ($test->getRunner()->getOutput()->isVeryVerbose()) {
        Feedback::$testDetails->write('├── ' . Color::wrap('green', $message));
      }
    }

    $why = strval($assert);
    if (!$assert->getResult()) {
      if (!empty($definition['why'])) {
        $why = "{$definition['why']} $why";
      }
      $why && $this->fail("├── $why");
      $reason = $assert->getReason();
      $reason && $this->failReason("│   └── $reason");
    }
    else {
      $why = $definition['why'] ?? $why;
      $why && $this->pass("├── $why");
    }


    $this->totalAssertions++;
    if (!$assert->getResult()) {
      $this->failedAssertionCount++;
    }

    return $assert;
  }

  /**
   * Add a debug message.
   *
   * @param string $message
   *   The debug message.
   */
  public function debug(string $message) {
    $this->messages[] = ['data' => $message, 'level' => 'debug'];
  }

  /**
   * Add an info message.
   *
   * @param string $message
   *   The debug message.
   */
  public function info(string $message) {
    $this->messages[] = ['data' => $message, 'level' => 'info'];
  }

  /**
   * Add a failure reason.
   *
   * @param string $message
   */
  public function fail(string $message) {
    $this->messages[] = ['data' => $message, 'level' => 'error'];
  }

  /**
   * Add a failure reason.
   *
   * @param string $message
   */
  protected function failReason(string $message) {
    $this->messages[] = [
      'data' => $message,
      'level' => 'error',
      'tags' => ['todo'],
    ];
  }

  /**
   * Add a pass reason.
   *
   * @param string $message
   *   The message.
   */
  protected function pass(string $message) {
    $this->messages[] = ['data' => $message, 'level' => 'success'];
  }

  /**
   * Get a runner storage instance.
   *
   * This should be used to share data across test suites, as it will persist
   * for the life of a given runner instance.
   *
   * @return \AKlump\CheckPages\StorageInterface
   *   A persistent storage across all test suites for a given runner instance.
   */
  public function getStorage(): StorageInterface {
    if ($this->storage) {
      return $this->storage;
    }

    $storage_name = NULL;
    $path_to_storage = $this->getPathToFilesDirectory() . '/storage/';

    $files = new Files($this);
    $path_to_storage = $files->prepareDirectory($path_to_storage);

    if (is_dir($path_to_storage)) {
      $storage_name = pathinfo($this->runner['name'], PATHINFO_FILENAME);
      $storage_name = rtrim($path_to_storage, '/') . "/$storage_name";
    }
    else {
      if ($this->debugging) {
        $this->debug(sprintf('├── To enable disk storage (i.e., sessions) create a writeable directory at %s.', $path_to_storage));
      }
    }
    $this->storage = new Storage($storage_name);

    return $this->storage;
  }

  /**
   * Return the path to the files, if it exists.
   *
   * @return string
   *   The path to the files directory or empty string.
   */
  public function getPathToFilesDirectory(): string {
    if (NULL === $this->pathToFiles) {
      if (empty($this->getConfig()['files'])) {
        if ($this->getOutput()->isDebug()) {
          $this->debug('├── To enable file output you must set a value for "files" in your config.');
        }
        $this->pathToFiles = '';
      }
      else {
        try {
          $this->pathToFiles = $this->resolve($this->getConfig()['files']);
        }
        catch (\Exception $exception) {
          $this->pathToFiles = '';
        }
      }
    }

    return $this->pathToFiles;
  }

  public function getPathToRunnerFilesDirectory(): string {
    $runner_files_path = '';
    $path_to_files = $this->getPathToFilesDirectory();
    if ($path_to_files) {
      $runner_files_path = $path_to_files . '/' . pathinfo($this->runner['name'], PATHINFO_FILENAME);
      if (!is_dir($runner_files_path)) {
        mkdir($runner_files_path, 0775, TRUE);
      }
    }

    return $runner_files_path;
  }

  /**
   * Send data to a file in the files directory if it exists.
   *
   * @param string $name
   *   A root value to use when generating filenames.
   * @param array $content
   *   The content to write to the file.
   *
   * @return $this
   */
  public function writeToFile(string $name, array $content, string $mode = 'a+'): self {
    $path_to_files = $this->getPathToRunnerFilesDirectory();
    if (!$path_to_files) {
      return $this;
    }
    if (empty($this->writeToFileResources[$name])) {
      $path = [];
      $path[] = pathinfo($name, PATHINFO_FILENAME);
      $extension = pathinfo($name, PATHINFO_EXTENSION) ?? '';
      $extension = $extension ?: 'txt';
      $path = implode('-', $path) . ".$extension";
      $handle = fopen($path_to_files . '/' . $path, $mode);
      $this->writeToFileResources[$name] = [$handle, $name, $path];
    }
    list($handle) = $this->writeToFileResources[$name];
    fwrite($handle, implode(PHP_EOL, $content) . PHP_EOL);

    return $this;
  }

  /**
   * Close out file resources if any.
   */
  public function __destruct() {
    $resources = $this->writeToFileResources ?? [];
    foreach ($resources as $info) {
      list($handle, $name) = $info;

      // TODO Put the date in the correct local timezone.
      $this->writeToFile($name, ['', '', date('r'), str_repeat('-', 80), '']);
      fclose($handle);
    }
  }

  /**
   * @return array
   */
  public function getMessages(): array {
    return $this->messages;
  }

}
