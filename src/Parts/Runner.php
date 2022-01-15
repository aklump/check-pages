<?php

namespace AKlump\CheckPages\Parts;

use AKlump\CheckPages\Assert;
use AKlump\CheckPages\ChromeDriver;
use AKlump\CheckPages\GuzzleDriver;
use AKlump\CheckPages\Output\FailedTestMarkdown;
use AKlump\CheckPages\PluginsManager;
use AKlump\CheckPages\SerializationTrait;
use AKlump\CheckPages\Storage;
use AKlump\CheckPages\SuiteFailedException;
use AKlump\CheckPages\TestFailedException;
use AKlump\CheckPages\UnresolvablePathException;
use AKlump\LoftLib\Bash\Color;
use GuzzleHttp\Exception\ServerException;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Yaml\Yaml;

class Runner {

  use SerializationTrait;

  /**
   * The filename without extension or path.
   *
   * @var string
   */
  const SCHEMA_VISIT = 'schema.visit.DO_NOT_EDIT';

  protected $totalTestCount = 0;

  protected $failedTestCount = 0;

  protected $failedSuiteCount = 0;

  protected $storage;

  /**
   * Holds a true state only when the filter is set and after a suite matching
   * the filter is used.  If a filter is set and all suites are run and this is
   * still false, it means that the filter was for a suite that was not
   * registered in the runner.
   *
   * @var bool
   */
  protected $filterApplied = FALSE;

  protected $filters = [];

  /**
   * @var int
   */
  protected $longestUrl = 0;

  /**
   * @var bool
   */
  protected $preflight;

  /**
   * @var array
   */
  protected $resolvePaths = [];

  /**
   * @var string
   */
  protected $pathToSuites = '';

  /**
   * @var bool
   */
  protected $outcome = TRUE;

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
  protected $debug = [];

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
   * Cache of discovered filepath.
   *
   * @var string
   */
  private $pathToFiles;

  /**
   * App constructor.
   *
   * @param string $root_dir
   *   The system path to this test app root directory.  The schema files, for
   *   example are found in this directory.
   * @param \AKlump\LoftLib\Bash\Bash $bash
   *   An instance of \AKlump\LoftLib\Bash\Bash.
   */
  public function __construct(string $root_dir) {
    $this->rootDir = $root_dir;
    $this->addResolveDirectory($this->rootDir);
    $this->addResolveDirectory($this->rootDir . '/tests');
    $this->pluginsManager = new PluginsManager($this, $this->rootDir . '/plugins');
    $schema = [];
    $schema_path = $this->rootDir . '/' . static::SCHEMA_VISIT . '.json';
    if (file_exists($schema_path)) {
      $schema = json_decode(file_get_contents($schema_path), TRUE);
    }
    $this->pluginsManager->setSchema($schema);
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
  public function setRunner(string $basename, array $options): Runner {
    if (strstr($basename, '/') !== FALSE) {
      throw new \InvalidArgumentException(sprintf('::setRunner() only takes a basename, not a full path; change "%s" to "%s"', $basename, basename($basename)));
    }
    $this->runner = [
      'name' => $basename,
      'options' => $options,
    ];
    $this->debugging = !in_array('quiet', $options);

    return $this;
  }

  /**
   * Set the config file.
   *
   * @param string $path
   *   A resolvable path to the config file.
   *
   * @return
   *   Self for chaining.
   *
   * @see load_config()
   */
  public function setConfig(string $path): Runner {
    $this->configPath = $path;

    return $this;
  }

  /**
   * Set the suite filter.
   *
   * All other suites will be ignored when set.
   *
   * @param string $filter
   *   The suite id to filter by.
   *
   * @return
   *   Self for chaining.
   *
   * @deprecated Use addSuiteFilter instead.
   */
  public function setSuiteFilter(string $filter): Runner {
    $this->filters = [$filter];

    return $this;
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
  public function addSuiteFilter(string $filter): Runner {
    if (strstr($filter, ',')) {
      throw new \InvalidArgumentException(sprintf('$filter may not contain a comma; the value %s is invalid.', $filter));
    }
    $this->filters[] = $filter;

    return $this;
  }

  /**
   * Reduce an array of suites by the applied filter(s), if any.
   *
   * @param array $suites
   *   An array of suite names.
   *
   * @return array
   *   Only those that match applied filters.
   */
  public function filterSuites(array $suites): array {
    if (!$this->filters) {
      return $suites;
    }

    $suites = array_map(function ($suite) {
      return pathinfo($suite, PATHINFO_FILENAME);
    }, $suites);

    return array_values(array_intersect($suites, $this->filters));
  }

  public function getTestOptions(): array {
    return $this->options;
  }

  public function getSuite() {
    return $this->suite ?? NULL;
  }

  /**
   * Add a custom command.
   *
   * @param string $name
   *   The unique command name.
   * @param callable $callback
   *   The callback function to execute.
   *
   * @return \AKlump\CheckPages\CheckPages
   *   Self for chaining.
   */
  public function addTestOption(string $name, array $callbacks): self {
    $this->options[$name] = ['name' => $name, 'hooks' => []];
    foreach ($callbacks as $hook => $callback) {
      $cb = new \ReflectionFunction($callback);
      $arguments = array_map(function ($param) {
        return [
          (string) $param->getType(),
          $param->getName(),
        ];
      }, $cb->getParameters());
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
   * @return \AKlump\CheckPages\CheckPages
   *   Self for chaining.
   */
  public function addResolveDirectory(string $path): self {
    $path = rtrim($path, '/');
    if (!in_array($path, $this->resolvePaths)) {
      $this->resolvePaths[] = $path;
    }

    return $this;
  }

  /**
   * @param string $path
   *   This directory will be used for resolving globs.
   *
   * @return \AKlump\CheckPages\CheckPages
   *   Self for chaining.
   */
  public function setPathToSuites(string $path): self {
    if (!is_dir($path)) {
      throw new \InvalidArgumentException("The suites directory \"$path\" does not exist.");
    }
    $this->pathToSuites = rtrim($path, '/');

    return $this;
  }

  /**
   * Get directory to the test suites.
   *
   * @return string
   *   Path to the suites directory.
   */
  public function getPathToSuites(): string {
    return $this->pathToSuites;
  }

  /**
   * Get the resolved test filepath.
   *
   * @return string
   *   The resolved test filepath.
   */
  public function getRunner(): string {
    try {
      return $this->resolve((string) $this->runner['name']);
    }
    catch (\Exception $exception) {
      return '';
    }
  }

  /**
   * Run a test file.
   *
   * @param string $path
   *   A resolvable path to a PHP runner file.
   *
   * @throws \RuntimeException If the test completed with failures.
   * @throws \AKlump\CheckPages\SuiteFailedException If the runner stopped
   *   before it was finished due to a failure.
   * @throws \AKlump\CheckPages\TestFailedException If the runner stopped
   *   before it was finished due to a failure.
   */
  public function executeRunner(string $path) {
    try {
      $runner = $this->getRunner();
      $filter_message = '';
      if ($this->filters) {
        $filter_message = sprintf(' (using suite filter(s) "%s")', implode(', ', $this->filters));
      }
      echo Color::wrap('blue', sprintf('Testing started with "%s"%s', basename($runner), $filter_message)) . PHP_EOL;
      $this->preflight = TRUE;
      require $runner;
      $this->preflight = FALSE;
      require $runner;

      if ($this->filters && !$this->filterApplied) {
        $code = array_map(function ($filter) {
          return sprintf("`run_suite('%s');`", $filter);
        }, $this->filters);
        throw new \RuntimeException(sprintf("The filter(s) were not applied; have you added %s to %s?", implode(', ', $code), $runner));
      }
    }
    catch (StopRunnerException $exception) {
      throw $exception;
    }

    if ($this->failedTestCount) {
      throw new \RuntimeException(sprintf("Testing complete with %d out of %d tests failing.", $this->failedTestCount, $this->totalTestCount));
    }
  }

  /**
   * Return the active configuration values.
   *
   * @return array
   *   The configuration array.
   */
  public function getConfig(): array {
    return Yaml::parseFile($this->getPathToConfig());
  }

  /**
   * Get path to active configuration file.
   *
   * @return string
   *   The absolute path to the active configuration.
   */
  public function getPathToConfig(): string {
    return $this->resolve($this->configPath);
  }

  /**
   * Visit an URLs definition found in $path.
   *
   * @param string $path
   *   A resolvable path to a yaml file.
   *
   * @throws \AKlump\CheckPages\TestFailedException
   * @throws \AKlump\CheckPages\SuiteFailedException
   * @see run_suite()
   *
   */
  public function runSuite(string $path_to_suite, array $suite_config = []) {
    $this->config = $suite_config + $this->getConfig();
    $this->validateConfig($this->config);

    $suite_id = '';
    $path_to_suite = $this->resolve($path_to_suite, $suite_id);
    $suite_id = pathinfo(substr($path_to_suite, strlen($suite_id) + 1), PATHINFO_FILENAME);

    $suite = new Suite($suite_id, $this->config, $this);
    $this->suite = $suite;

    $this->config['suites_to_ignore'] = array_filter(array_map(function ($suite_to_ignore) {
      try {
        return $this->resolve($suite_to_ignore);
      }
      catch (UnresolvablePathException $exception) {

        // If we're asked to ignore a suite that can't be resolved, then that is
        // not an exception in this case, we can easily ignore it because we
        // can't find it.  Return NULL, which will be filtered out.
        return NULL;
      }
    }, $this->config['suites_to_ignore'] ?? []));

    if ($this->filters) {
      $filters = array_map([$this, 'resolve'], $this->filters);
      if (!in_array($path_to_suite, $filters)) {
        return;
      }
    }

    if (in_array($path_to_suite, $this->config['suites_to_ignore'])) {
      if ($this->preflight) {
        echo PHP_EOL . Color::wrap('blue', '😴 ' . strtoupper(sprintf('Ignoring "%s" suite...', $suite_id))) . PHP_EOL;
      }

      return;
    }

    if ($this->filters) {
      $this->filterApplied = TRUE;
    }

    $tests = $this->validateAndLoadYaml($path_to_suite, static::SCHEMA_VISIT . '.json');
    $this->normalizeSuiteData($tests);

    $this->longestUrl = array_reduce($tests, function ($carry, $item) {
      $url = is_string($item) ? $item : ($item['url'] ?? '');

      return max($carry, strlen($url));
    }, $this->longestUrl);
    $results = [];

    // The preflight is to determine the longest URL so that all our tables are
    // the same width.
    if ($this->preflight) {
      return;
    }

    if (!$this->debugging && empty($this->printed['base_url'])) {
      echo Color::wrap('white on blue', sprintf('Base URL is %s', $this->config['base_url'])) . PHP_EOL;
      $this->printed['base_url'] = TRUE;
    }
    echo PHP_EOL . Color::wrap('blue', '⏱  ' . strtoupper(sprintf('Running "%s" suite...', $suite_id))) . PHP_EOL;

    $this->debug = [];
    $failed_tests = 0;

    foreach (array_values($tests) as $test_index => $config) {
      if (isset($config['set'])) {
        $suite->variables()->setItem($config['set'], $config['is']);
        continue;
      }
      $config += [
        'expect' => NULL,
        'find' => '',
      ];
      $test = new Test(strval($test_index), $config, $suite);
      $suite->addTest($test);
    }

    $has_multiple_methods = count($suite->getHttpMethods()) > 1;
    foreach ($suite->getTests() as $test_index => $test) {
      $config = $test->getConfig();

      if (count($suite->variables())) {
        foreach ($config as $key => &$item) {
          // We must not interpolate `find` at this time, that will take place
          // inside of the handleFindAssert method.  That is because variables
          // can be set on every assert.  However the rest of the config should
          // be interpolated, such as will affect the URL.
          if ($key !== 'find') {
            $item = $suite->variables()->interpolate($item);
          }
        }
      }

      if (!empty($config['why'])) {
        echo '🔎 ' . Color::wrap('blue', $config['why']) . PHP_EOL;
      }

      $method = $has_multiple_methods ? $test->getHttpMethod() : '';

      // Print the URL before we run the test so it appears before the user has to wait.
      $url = $this->debugging ? $this->url($config['url']) : $config['url'];
      echo Color::wrap('blue', ltrim("$method $url ", ' '));

      if ($config['js'] ?? FALSE) {
        echo "☕ ";
      }
      $test->setConfig($config);

      $result = $this->runTest($test);

      // This icon will affix itself to the URL after the test.
      $icon = $result['pass'] ? '👍' : '🚫';
      echo "$icon" . PHP_EOL;

      // Create the failure output files.
      if (!$result['pass']) {
        $this->writeToFile('urls', [$url]);
        $failure_log = [$url];
        foreach ($this->debug as $item) {
          if ('error' === $item['level']) {
            $failure_log[] = $item['data'];
          }
        }
        $failure_log[] = PHP_EOL;
        $this->writeToFile('failures', $failure_log);

        FailedTestMarkdown::output("{$suite_id}{$test_index}", $test);
      }

      if ($this->debugging && $this->debug) {
        $this->echoMessages();
        echo PHP_EOL;
      }

      // Decide if we should stop the runner or not.
      if (!$result['pass']) {
        $this->failedTestCount++;
        $failed_tests++;
        if ($this->config['stop_on_failed_test'] ?? FALSE) {
          throw new TestFailedException($config);
        }
      }
    }

    if ($failed_tests) {
      $this->failedSuiteCount++;
      if ($this->config['stop_on_failed_suite'] ?? FALSE) {
        throw new SuiteFailedException($suite_id, $results);
      }
    }
  }

  protected function echoMessages() {
    $color_map = [
      'error' => 'red',
      'info' => 'blue',
      'success' => 'green',
      'debug' => 'dark gray',
    ];
    $messages = array_map(function ($item) use ($color_map) {
      return Color::wrap($color_map[$item['level']], $item['data']);
    }, $this->debug);
    echo implode(PHP_EOL, $messages) . PHP_EOL;
    $this->debug = [];
  }

  /**
   * Handle visitation to a single URL.
   *
   * @param array $config
   *
   * @return array
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  protected function runTest(Test $test): array {
    $config = $test->getConfig();
    // Ensure find is an array so we don't have to check below in two places.
    if (!is_array($config['find'])) {
      $config['find'] = empty($config['find']) ? [] : [$config['find']];
    }

    $this->pluginsManager->onBeforeDriver($config);

    $test_passed = function (bool $result = NULL): bool {
      static $state;
      if (!is_null($result)) {
        $state = is_null($state) || $state ? $result : FALSE;
      }

      return boolval($state);
    };

    $this->totalTestCount++;

    if ($config['js'] ?? FALSE) {
      try {
        if (empty($this->config['chrome'])) {
          throw new \InvalidArgumentException(sprintf("Javascript testing is unavailable due to missing path to Chrome binary.  Add \"chrome\" in file %s.", $this->resolve($this->configPath)));
        }
        $driver = new ChromeDriver($this->config['chrome']);
      }
      catch (\Exception $exception) {
        throw new TestFailedException($config, $exception);
      }
    }
    else {
      $driver = new GuzzleDriver();
    }
    $this->pluginsManager->onBeforeRequest($driver);


    // This will show the request headers and body if asked
    if (array_intersect_key(array_flip([
      'show-request',
      'show-source',
    ]), $this->runner['options'])) {
      $this->debug($driver . '---' . PHP_EOL);
    }

    try {
      $response = $driver
        ->setUrl($this->url($config['url']))
        ->getResponse();
    }
    catch (ServerException $exception) {
      $response = $exception->getResponse();
    }

    $http_location = NULL;

    // If not specified, then any 2XX will pass.
    $http_response_code = $response->getStatusCode();
    if (empty($config['expect'])) {
      $test_passed($http_response_code >= 200 && $http_response_code <= 299);
    }
    else {
      if ($config['expect'] >= 300 && $config['expect'] <= 399) {
        $http_location = $driver->getLocation();
        $http_response_code = $driver->getRedirectCode();
      }

      $test_passed($http_response_code == $config['expect']);
    }

    if (array_intersect_key(array_flip([
      'show-response',
      'show-source',
    ]), $this->runner['options'])) {
      $show_source = (string) $response->getBody();

      // Try to make it more readable if we can.
      $content_type = $this->getContentType($response);
      if (strstr($content_type, 'json')) {
        $show_source = $this->deserialize($show_source, $content_type);
        $show_source = json_encode($show_source, JSON_PRETTY_PRINT);
      }

      if ($test_passed()) {
        $this->debug($show_source);
      }
      else {
        $this->fail($show_source);
      }
    }

    if ($test_passed()) {
      $this->pass('├── HTTP ' . $http_response_code);
    }
    else {
      $this->failReason(sprintf("├── Expected HTTP %s, got %d", $config['expect'] ?? '2xx', $http_response_code));
    }

    // Test the location if asked.
    $expected_location = $config['location'] ?? '';
    if (empty($expected_location)) {
      $expected_location = $config['redirect'] ?? '';
    }
    if ($http_location && $expected_location) {
      $location_test = $http_location === $this->url($expected_location);
      $test_passed($location_test);
      if (!$location_test) {
        $this->failReason(sprintf('├── The actual location: %s did not match the expected location: %s', $http_location, $this->url($expected_location)));
      }
    }

    if (empty($config['find']) && $this->debugging) {
      $this->debug('├── The test has provided NO assertions.');
    }
    $assertions = $config['find'];
    $id = 0;
    while ($definition = array_shift($assertions)) {
      if (is_scalar($definition)) {
        $definition = [Assert::ASSERT_CONTAINS => $definition];
      }
      $test_passed($this->handleFindAssert(strval($id), $definition, $response));
      ++$id;
    }

    if ($test_passed()) {
      $this->pass('└── Test passed.');
    }
    else {
      $this->fail('└── Test failed.');
    }

    $test->setResults($this->debug);

    return [
      'pass' => $test_passed(),
      'status' => $http_response_code,
    ];
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
   * @throws \AKlump\CheckPages\UnresolvablePathException
   *   If the path cannot be resolved.
   */
  public function resolve(string $path, &$resolved_path = NULL) {
    $candidates = [['', $path]];
    foreach ($this->resolvePaths as $resolve_path) {
      $resolve_path = rtrim($resolve_path, '/');
      $candidates[] = [$resolve_path, $resolve_path . '/' . $path];
      $candidates[] = [$resolve_path, $resolve_path . '/' . $path . '.yml'];
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

    return rtrim($this->config['base_url'], '/') . '/' . trim($possible_relative_url, '/');
  }

  /**
   * Load YAML from a file first validating against the schema.
   *
   * @param string $path
   * @param string $schema_basename
   *
   * @return array
   */
  protected function validateAndLoadYaml(string $path, string $schema_basename): array {
    $data = Yaml::parseFile($this->resolve($path), Yaml::PARSE_OBJECT_FOR_MAP);
    $validator = new Validator();
    $path_to_schema = $this->rootDir . '/' . $schema_basename;
    $validator->validate($data, (object) ['$ref' => 'file://' . $path_to_schema]);
    if (!$validator->isValid()) {
      $message = [
        sprintf('Syntax error in suite: "%s" using "%s"', basename($path), $schema_basename),
        NULL,
      ];
      foreach ($validator->getErrors() as $error) {
        $message[] = sprintf("[%s] %s", $error['property'], $error['message']);
      }
      throw new \RuntimeException(implode(PHP_EOL, $message));
    }

    // Convert to arrays, we only needed objects for the validation.
    return json_decode(json_encode($data), TRUE);
  }

  /**
   * Validate a configuration array against the configuration schema.
   *
   * @param array $config
   *
   * @throws \Exception
   */
  protected function validateConfig(array &$config) {
    // Convert to objects.
    $config = json_decode(json_encode($config));
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
   * @return bool
   *   True if the find was successful.
   */
  protected function handleFindAssert(string $id, array $definition, ResponseInterface $response): bool {
    $definition = $this->getSuite()->variables()->interpolate($definition);
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

    $this->pluginsManager->onBeforeAssert($assert, $response);

    $assert->run();
    $pass = $assert->getResult();

    if ($assert->set) {
      $needle = $assert->getNeedle() ?? $assert->getHaystack()[0] ?? NULL;
      if (is_scalar($needle)) {
        $this->pass(sprintf('├── ${%s} set as "%s".', $assert->set, $needle));
      }
      else {
        $this->pass(sprintf('├── ${%s} set.', $assert->set));
      }
      $this->getSuite()->variables()->setItem($assert->set, $needle);
    }

    $why = strval($assert);
    if (!$pass) {
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

    return $pass;
  }

  /**
   * Add a debug message.
   *
   * @param string $message
   *   The debug message.
   */
  protected function debug(string $message) {
    $this->debug[] = ['data' => $message, 'level' => 'debug'];
  }

  /**
   * Add an info message.
   *
   * @param string $message
   *   The debug message.
   */
  protected function info(string $message) {
    $this->debug[] = ['data' => $message, 'level' => 'info'];
  }

  /**
   * Add a failure reason.
   *
   * @param string $message
   */
  protected function fail(string $message) {
    $this->debug[] = ['data' => $message, 'level' => 'error'];
  }

  /**
   * Add a failure reason.
   *
   * @param string $message
   */
  protected function failReason(string $message) {
    $this->debug[] = [
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
    $this->debug[] = ['data' => $message, 'level' => 'success'];
  }

  /**
   * Since we may have different schemas, this method will normalize them.
   *
   * Remap some schema keys to the original, base schema.
   *
   * @param array &$data
   *   The YAML data from a test suite.
   */
  protected function normalizeSuiteData(array &$data) {
    foreach ($data as &$test_data) {
      $keys = array_map(function ($key) {
        return $key === 'visit' ? 'url' : $key;
      }, array_keys($test_data));
      $test_data = array_combine($keys, $test_data);
    }
  }

  /**
   * Get a runner storage instance.
   *
   * This should be used to share data across test suites, as it will persiste
   * for the life of a given runner instance.
   *
   * @return \AKlump\CheckPages\StorageInterface
   *   A persistent storage across all test suites for a given runner instance.
   */
  public function getStorage(): \AKlump\CheckPages\StorageInterface {
    if ($this->storage) {
      return $this->storage;
    }

    $storage_name = NULL;
    $path_to_storage = $this->getPathToFilesDirectory() . '/storage/';
    if (is_dir($path_to_storage)) {
      $storage_name = pathinfo($this->runner['name'], PATHINFO_FILENAME);
      $storage_name = rtrim($path_to_storage, '/') . "/$storage_name";
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
  private function getPathToFilesDirectory(): string {
    if (NULL === $this->pathToFiles) {
      if (empty($this->config['files'])) {
        $this->pathToFiles = '';
      }
      else {
        try {
          $this->pathToFiles = $this->resolve($this->config['files']);
        }
        catch (\Exception $exception) {
          $this->pathToFiles = '';
        }
      }
    }

    return $this->pathToFiles;
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
    $path_to_files = $this->getPathToFilesDirectory();
    if (!$path_to_files) {
      return $this;
    }
    $path_to_files .= '/' . pathinfo($this->runner['name'], PATHINFO_FILENAME);
    if (empty($this->writeToFileResources[$name])) {

      if (!is_dir($path_to_files)) {
        mkdir($path_to_files, 0775, TRUE);
      }

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
      $this->writeToFile($name, ['', '', date('r'), str_repeat('-', 80), '']);
      fclose($handle);
    }
  }

}
