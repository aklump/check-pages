<?php

namespace AKlump\CheckPages;

use AKlump\LoftLib\Bash\Bash;
use AKlump\LoftLib\Bash\Color;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;
use Symfony\Component\Yaml\Yaml;

class CheckPages {

  protected $totalTestCount = 0;

  protected $failedTestCount = 0;

  protected $failedSuiteCount = 0;

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
    $this->addResolveDirectory($this->rootDir . '/tests/');
    $this->bash = $bash;
    $this->debugging = !$bash->hasParam('quiet');
  }

  /**
   * Set the config file.
   *
   * @param string $path
   *   A resolvable path to the config file.
   *
   * @see load_config()
   */
  public function setConfig(string $path) {
    $this->configPath = $path;
  }

  /**
   * @param string $path
   *   An absolute path to a directory to be used for resolving paths.
   */
  public function addResolveDirectory(string $path) {
    $this->resolvePaths[] = $path;
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
      echo Color::wrap('blue', sprintf('Testing started with "%s"', basename($runner))) . PHP_EOL;
      $this->preflight = TRUE;
      require $runner;
      $this->preflight = FALSE;
      require $runner;
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
  public function runSuite(string $path) {
    $this->config = $this->validateAndLoadYaml($this->configPath, 'schema.config.json');

    $suite_id = '';
    $path = $this->resolve($path, $suite_id);
    $suite_id = pathinfo(substr($path, strlen($suite_id) + 1), PATHINFO_FILENAME);

    $this->config['suites_to_ignore'] = array_map(function ($suite_to_ignore) {
      return $this->resolve($suite_to_ignore);
    }, $this->config['suites_to_ignore'] ?? []);

    if (in_array($path, $this->config['suites_to_ignore'])) {
      if ($this->preflight) {
        echo PHP_EOL . Color::wrap('blue', '😴 ' . strtoupper(sprintf('Ignoring "%s" suite...', $suite_id))) . PHP_EOL;
      }

      return;
    }

    $data = $this->validateAndLoadYaml($path, 'schema.visit.json');

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
    $this->totalTestCount++;
    $client = new Client([

      // @link http://docs.guzzlephp.org/en/stable/faq.html#how-can-i-track-redirected-requests
      RequestOptions::ALLOW_REDIRECTS => [
        'max' => 10,        // allow at most 10 redirects.
        'strict' => TRUE,      // use "strict" RFC compliant redirects.
        'referer' => TRUE,      // add a Referer header
        'track_redirects' => TRUE,
      ],
    ]);
    try {
      $response = $client->request('GET', $this->url($config['url']));
    }
    catch (ClientException $exception) {
      $response = $exception->getResponse();
    }

    $http_location = NULL;
    if ($config['expect'] >= 300 && $config['expect'] <= 399) {
      $http_location = $response->getHeader('X-Guzzle-Redirect-History');
      $http_location = array_last($http_location);
      $http_response_code = $response->getHeader('X-Guzzle-Redirect-Status-History')[0];
    }
    else {
      $http_response_code = $response->getStatusCode();
    }

    $test_passed = $http_response_code == $config['expect'];

    if ($this->bash->hasParam('show-source')) {
      if ($test_passed) {
        $this->debug((string) $response->getBody());
      }
      else {
        $this->fail((string) $response->getBody());
      }
    }

    if ($http_response_code == $config['expect']) {
      $this->pass('├── HTTP ' . $http_response_code);
    }
    else {
      $this->fail(sprintf("├── Expected HTTP %d, got %d", $config['expect'], $http_response_code));
    }

    // Test the location if asked.
    if ($http_location && $config['location']) {
      $test_passed = $http_location === $this->url($config['location']);
      if (!$test_passed) {
        $this->fail(sprintf('├── The actual location: %s did not match the expected location: %s', $http_location, $config['location']));
      }
    }

    // Look for a piece of text on the page.
    if ($config['find']) {
      $body = strval($response->getBody());
      foreach ($config['find'] as $needle) {
        $assert = $this->handleFindAssert($needle, $body);
        $test_passed = $test_passed ? $assert : FALSE;
      }
    }

    if ($test_passed) {
      $this->pass('└── Test passed.');
    }
    else {
      $this->fail('└── Test failed.');
    }

    return [
      'pass' => $test_passed,
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
   */
  protected function resolve(string $path, &$resolved_path = NULL) {
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
    throw new \InvalidArgumentException("Cannot resolve \"$path\".");
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
      throw new $class(sprintf('%s using %s: %s', $path, $schema_basename, $exception->getMessage()), $exception->getCode(), $exception);
    }

    // Convert to arrays, we only needed objects for the validation.
    return json_decode(json_encode($data), TRUE);
  }

  /**
   * Apply a single find action in the text.
   *
   * @param array|string $needle
   *   When a string a case-sensitive search will be made in $haystack.  As an
   *   array it should contain an "expect" key and a key "dom", which is a CSS
   *   selector.
   * @param string $haystack
   *   The large string to search within.
   *
   * @return bool
   *   True if the find was successful.
   */
  protected function handleFindAssert($needle, string $haystack): bool {
    $assert = new Assert($haystack);

    // Set up the search.
    if (!is_array($needle)) {
      $assert->setSearch(Assert::SEARCH_ALL);
    }
    elseif (isset($needle['dom'])) {
      $assert->setSearch(Assert::SEARCH_DOM, $needle['dom']);
    }
    elseif (isset($needle['xpath'])) {
      $assert->setSearch(Assert::SEARCH_XPATH, $needle['xpath']);
    }
    elseif (isset($needle['match'])) {
      $assert->setSearch(Assert::SEARCH_ALL);
    }

    // Setup the assert.
    if (!is_array($needle)) {
      $assert->setAssert(Assert::ASSERT_SUBSTRING, $needle);
    }
    elseif (isset($needle['text'])) {
      $assert->setAssert(Assert::ASSERT_TEXT, $needle['text']);
    }
    elseif (isset($needle['count'])) {
      $assert->setAssert(Assert::ASSERT_COUNT, $needle['count']);
    }
    elseif (isset($needle['exact'])) {
      $assert->setAssert(Assert::ASSERT_EXACT, $needle['exact']);
    }
    elseif (isset($needle['match'])) {
      $assert->setAssert(Assert::ASSERT_MATCH, $needle['match']);
    }

    $pass = $assert->run();
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

}
