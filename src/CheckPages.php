<?php

namespace AKlump\CheckPages;

use AKlump\LoftLib\Bash\Bash;
use AKlump\LoftLib\Bash\Color;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;
use Psr\Http\Message\ResponseInterface;
use ReflectionFunction;
use Symfony\Component\Yaml\Yaml;

class CheckPages {

  protected $totalTestCount = 0;

  protected $failedTestCount = 0;

  protected $failedSuiteCount = 0;

  /**
   * Holds a true state only when the filter is set and after a suite matching
   * the filter is used.  If a filter is set and all suites are run and this is
   * still false, it means that the filter was for a suite that was not
   * registered in the runner.
   *
   * @var bool
   */
  protected $filterApplied = FALSE;

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
  protected $commands = [];

  /**
   * App constructor.
   *
   * @param string $root_dir
   *   The system path to this test app root directory.  The schema files, for
   *   example are found in this directory.
   * @param \AKlump\LoftLib\Bash\Bash $bash
   *   An instance of \AKlump\LoftLib\Bash\Bash.
   */
  public function __construct(string $root_dir, Bash $bash) {
    $this->rootDir = $root_dir;
    $this->addResolveDirectory($this->rootDir);
    $this->addResolveDirectory($this->rootDir . '/tests');
    $this->bash = $bash;
    $this->debugging = !$bash->hasParam('quiet');

    $this->pluginsManager = new PluginsManager($this->rootDir . '/plugins');
    $schema = json_decode(file_get_contents($this->rootDir . '/schema.visit.json'), TRUE);
    $this->pluginsManager->setSchema($schema);
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
  public function setConfig(string $path): CheckPages {
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
   */
  public function setSuiteFilter(string $filter): CheckPages {
    $this->filter = $filter;

    return $this;
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
  public function addCommand(string $name, callable $callback): self {
    $cb = new ReflectionFunction($callback);
    $arguments = array_map(function ($param) {
      return [
        (string) $param->getType(),
        $param->getName(),
      ];
    }, $cb->getParameters());
    $this->commands[$name] = [
      'name' => $name,
      'arguments' => $arguments,
      'callback' => $callback,
    ];

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
      return $this->resolve((string) $this->bash->getArg(1));
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
  public function run(string $path) {
    try {
      $runner = $this->getRunner();
      $filter_message = '';
      if ($this->filter) {
        $filter_message = sprintf(' (using suite filter "%s")', $this->filter);
      }
      echo Color::wrap('blue', sprintf('Testing started with "%s"%s', basename($runner), $filter_message)) . PHP_EOL;
      $this->preflight = TRUE;
      require $runner;
      $this->preflight = FALSE;
      require $runner;

      if ($this->filter && !$this->filterApplied) {
        throw new \RuntimeException(sprintf("The filter was not applied; have you added `run_suite('%s');` to %s?", $this->filter, $runner));
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
    $this->config = $suite_config + Yaml::parseFile($this->resolve($this->configPath));
    $this->validateConfig($this->config);

    $suite_id = '';
    $path_to_suite = $this->resolve($path_to_suite, $suite_id);
    $suite_id = pathinfo(substr($path_to_suite, strlen($suite_id) + 1), PATHINFO_FILENAME);

    $this->config['suites_to_ignore'] = array_map(function ($suite_to_ignore) {
      return $this->resolve($suite_to_ignore);
    }, $this->config['suites_to_ignore'] ?? []);

    if (in_array($path_to_suite, $this->config['suites_to_ignore'])) {
      if ($this->preflight) {
        echo PHP_EOL . Color::wrap('blue', '😴 ' . strtoupper(sprintf('Ignoring "%s" suite...', $suite_id))) . PHP_EOL;
      }

      return;
    }

    if ($this->filter) {
      if ($this->resolve($this->filter) !== $path_to_suite) {
        return;
      }
      $this->filterApplied = TRUE;
    }

    $data = $this->validateAndLoadYaml($path_to_suite, 'schema.visit.json');
    $this->normalizeSuiteData($data);

    $this->longestUrl = array_reduce($data, function ($carry, $item) {
      return max($carry, strlen($item['url'] ?? $item));
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
    foreach ($data as $config) {
      $config += [
        'expect' => 200,
        'find' => '',
      ];
      $result = $this->runTest($config);
      $color = $result['pass'] ? 'green' : 'white on red';
      $prefix = $result['pass'] ? '👍' : '👎';
      $prefix = $result['pass'] ? '👍' : '🚫';
      $url = $this->debugging ? $this->url($config['url']) : $config['url'];
      echo $prefix . ' ' . Color::wrap($color, $url) . PHP_EOL;

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
      'success' => 'green',
      'debug' => 'light gray',
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
  protected function runTest(array $config): array {
    $this->pluginsManager->onBeforeDriver($config);

    $test_passed = function (bool $result = NULL): bool {
      static $state;
      if (!is_null($result)) {
        $state = is_null($state) || $state ? $result : FALSE;
      }

      return boolval($state);
    };

    $this->totalTestCount++;

    // Ensure find is an array so we don't have to check below in two places.
    if (!is_array($config['find'])) {
      $config['find'] = empty($config['find']) ? [] : [$config['find']];
    }

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

    $response = $driver
      ->setUrl($this->url($config['url']))
      ->getResponse();

    $http_location = NULL;
    if ($config['expect'] >= 300 && $config['expect'] <= 399) {
      $http_location = $driver->getLocation();
      $http_response_code = $driver->getRedirectCode();
    }
    else {
      $http_response_code = $response->getStatusCode();
    }

    $test_passed($http_response_code == $config['expect']);

    if ($this->bash->hasParam('show-source')) {
      if ($test_passed()) {
        $this->debug((string) $response->getBody());
      }
      else {
        $this->fail((string) $response->getBody());
      }
    }

    if ($test_passed()) {
      $this->pass('├── HTTP ' . $http_response_code);
    }
    else {
      $this->fail(sprintf("├── Expected HTTP %d, got %d", $config['expect'], $http_response_code));
    }

    // Test the location if asked.
    if ($http_location && $config['location']) {
      $location_test = $http_location === $this->url($config['location']);
      $test_passed($location_test);
      if (!$location_test) {
        $this->fail(sprintf('├── The actual location: %s did not match the expected location: %s', $http_location, $this->url($config['location'])));
      }
    }

    // Look for a piece of text on the page.
    foreach ($config['find'] as $index => $needle) {
      if (is_scalar($needle)) {
        $needle = [Assert::ASSERT_SUBSTRING => $needle];
      }
      $test_passed($this->handleFindAssert($index, $needle, $response));
    }

    if ($test_passed()) {
      $this->pass('└── Test passed.');
    }
    else {
      $this->fail('└── Test failed.');
    }

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
      if (is_file($try)) {
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
  protected function url(string $possible_relative_url): string {
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
    try {
      $validator->validate($data, (object) ['$ref' => 'file://' . $this->rootDir . '/' . $schema_basename], Constraint::CHECK_MODE_EXCEPTIONS);
    }
    catch (\Exception $exception) {

      // Add in the file context.
      $class = get_class($exception);
      throw new $class(sprintf('%s using %s : %s', $path, $schema_basename, $exception->getMessage()), $exception->getCode(), $exception);
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
   * @param array|string $needle
   *   When a string a case-sensitive search will be made in $haystack.  As an
   *   array it should contain an "expect" key and a key "dom", which is a CSS
   *   selector.
   * @param \Psr\Http\Message\ResponseInterface
   *   The response containing the custom headers and body.
   *
   * @return bool
   *   True if the find was successful.
   */
  protected function handleFindAssert($index, array $needle, ResponseInterface $response): bool {
    $assert = new Assert($needle, strval($index));
    $selectors = array_map(function ($help) {
      return $help->code();
    }, $assert->getSelectorsInfo());

    foreach ($selectors as $code) {
      if (isset($needle[$code])) {
        $assert->setSearch($code, $needle[$code]);
        if (!empty($needle[Assert::MODIFIER_ATTRIBUTE])) {
          $assert->setModifer(Assert::MODIFIER_ATTRIBUTE, $needle[Assert::MODIFIER_ATTRIBUTE]);
        }
        break;
      }
    }

    // Setup the assert.
    $assertions = array_map(function ($help) {
      return $help->code();
    }, $assert->getAssertionsInfo());
    foreach ($assertions as $code) {
      if (isset($needle[$code])) {
        $assert->setAssertion($code, $needle[$code]);
        break;
      }
    }

    $this->pluginsManager->onBeforeAssert($assert, $response);

    // At this point if no search type then we fallback to SEARCH_ALL.
    if (empty($assert->getSearch()[0])) {
      $assert->setSearch(Assert::SEARCH_ALL);
    }
    if (empty($assert->getHaystack())) {
      // The fallback will be the body of the response.
      $assert->setHaystack([strval($response->getBody())]);
    }

    $assert->run();
    $pass = $assert->getResult();
    if (!$pass) {
      $this->fail('├── ' . $assert);
      $this->fail('└── ' . $assert->getReason());
    }
    else {
      $this->pass('├── ' . $assert);
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
   * Add a failure reason.
   *
   * @param string $reason
   */
  protected function fail(string $reason) {
    $this->debug[] = ['data' => $reason, 'level' => 'error'];
  }


  /**
   * Add a failure reason.
   *
   * @param string $reason
   *   The message.
   */
  protected function pass(string $reason) {
    $this->debug[] = ['data' => $reason, 'level' => 'success'];
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
    foreach ($data as &$datum) {
      $datum['url'] = $datum['visit'] ?? $datum['url'];
      unset($datum['visit']);
    }
  }

}
