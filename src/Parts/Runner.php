<?php

namespace AKlump\CheckPages\Parts;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\RunnerEvent;
use AKlump\CheckPages\Event\SuiteEvent;
use AKlump\CheckPages\Event\TestEvent;
use AKlump\CheckPages\Exceptions\StopRunnerException;
use AKlump\CheckPages\Exceptions\SuiteFailedException;
use AKlump\CheckPages\Exceptions\TestFailedException;
use AKlump\CheckPages\Exceptions\UnresolvablePathException;
use AKlump\CheckPages\Files;
use AKlump\CheckPages\Output\ConsoleEchoPrinter;
use AKlump\CheckPages\Output\DebugMessage;
use AKlump\CheckPages\Output\Message;
use AKlump\CheckPages\Output\SourceCodeOutput;
use AKlump\CheckPages\Output\VerboseDirective;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\CheckPages\Plugin\PluginsManager;
use AKlump\CheckPages\RequestDriverInterface;
use AKlump\CheckPages\SerializationTrait;
use AKlump\CheckPages\Storage;
use AKlump\CheckPages\StorageInterface;
use AKlump\CheckPages\Traits\HasSuiteTrait;
use AKlump\CheckPages\Traits\SetTrait;
use AKlump\Messaging\HasMessagesTrait;
use AKlump\Messaging\MessageType;
use AKlump\Messaging\MessengerInterface;
use JsonSchema\Validator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Yaml\Yaml;

class Runner {

  use HasSuiteTrait;
  use SerializationTrait;
  use SetTrait;
  use HasMessagesTrait;

  /**
   * The filename without extension or path.
   *
   * @var string
   */
  const SCHEMA_VISIT = 'schema.suite.DO_NOT_EDIT';

  const OUTPUT_NORMAL = 1;

  const OUTPUT_QUIET = 2;

  public $totalAssertions = 0;

  public $failedAssertionCount = 0;

  protected $totalTestsRun = 0;

  protected $outputMode;

  protected $failedTestCount = 0;

  protected $failedSuiteCount = 0;

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
   * Echo a message.
   *
   * Shorthand for $this->getMessenger()->deliver(...
   *
   * @param \AKlump\CheckPages\Output\Message $message
   * @param int|NULL $flags
   *
   * @return void
   *
   * @see \AKlump\CheckPages\Parts\Runner::getMessenger
   */
  public function echo(Message $message, int $flags = NULL) {
    $this->getMessenger()->deliver($message, $flags);
  }

  /**
   * Get a configured messenger for user feedback.
   *
   * @return \AKlump\Messaging\MessengerInterface
   *   A new new instance.
   */
  public function getMessenger(): MessengerInterface {
    $output = $this->getOutput();

    if ($output->isQuiet()) {
      return new DevNullPrinter();
    }

    $flags = Verbosity::NORMAL;
    $input = $this->getInput();
    $options = $input->getOptions();

    if ($options['verbose']) {
      $flags = $flags | Verbosity::VERBOSE;
    }

    // These are the CLI arguments: -vvv || --debug=true
    if ($output->isVeryVerbose() || $options['debug']) {
      $flags = $flags | Verbosity::DEBUG;
    }
    $show = new VerboseDirective(strval($options['show']));
    if ($show->showSendHeaders() || $show->showResponseHeaders()) {
      $flags = $flags | Verbosity::HEADERS;
    }
    if ($show->showSendBody()) {
      $flags = $flags | Verbosity::REQUEST;
    }
    if ($show->showResponseBody()) {
      $flags = $flags | Verbosity::RESPONSE;
    }

    return new ConsoleEchoPrinter($output, $flags);
  }

  public function getRootDir(): string {
    return $this->rootDir;
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
    foreach ($config['variables'] ?? [] as $key => $value) {
      $this->getSuite()->variables()->setItem($key, $value);
    }

    return $this;
  }

  /**
   * Return the runtime configuration values.
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

    $id_filters = $this->filters['id'] ?? [];
    $group_filters = $this->filters['group'] ?? [];
    $does_match_group = function (Suite $suite) use ($group_filters) {
      if (empty($group_filters)) {
        return TRUE;
      }

      return in_array($suite->getGroup(), $group_filters);
    };
    $does_match_id = function (Suite $suite) use ($id_filters) {
      if (empty($id_filters)) {
        return TRUE;
      }

      return in_array($suite->id(), $id_filters);
    };

    return array_filter($suites, function (Suite $suite) use ($does_match_group, $does_match_id) {
      $matched = $does_match_id($suite) && $does_match_group($suite);
      if ($matched) {
        $this->filtersWereApplied = TRUE;
      }

      return $matched;
    });
  }

  public function getTestOptions(): array {
    return $this->options;
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

      $this->echo(new Message(
        [
          sprintf('Testing started with %s%s', basename($runner_path), $filter_message),
          '',
        ],
        MessageType::INFO,
        Verbosity::VERBOSE
      ));

      require $runner_path;

      if (count($this->filters) > 0 && !$this->filtersWereApplied) {
        $this->echo(new Message(
          [
            '',
            sprintf('There are no suites in %s that match at least one of your filters.', basename($runner_path)),
            'This can happen if you have not added `run_suite()` with a path to the intended suite(s).',
            '',
          ],
          MessageType::ERROR
        ));
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
   * Run a suite.
   *
   * @param \AKlump\CheckPages\Parts\Suite $suite
   * @param string $path_to_suite
   *
   * @throws \AKlump\CheckPages\Exceptions\SuiteFailedException
   * @throws \AKlump\CheckPages\Exceptions\TestFailedException
   * @see run_suite()
   */
  public function run(Suite $suite, string $path_to_suite) {
    $this->setSuite($suite);

    // Allow the modification of the runner config before the suite is run.
    $this->getDispatcher()
      ->dispatch(new RunnerEvent($this), Event::RUNNER_CONFIG);

    // TODO Might be worth moving this out of this method to src/SuiteValidator.php...
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
    }, $this->getConfig()['suites_to_ignore'] ?? []));

    if (!$this->applyFilters([$suite])) {
      return;
    }

    if (in_array($path_to_suite, $ignored_suite_paths)) {
      $status = NULL;
      if ($this->getInput()->getOption('retest')) {
        $status = TRUE;
      }
      $this->echo(new Message(
        [' ↓  ' . $this->getSuite()],
        $status ? MessageType::SUCCESS : MessageType::INFO,
        Verbosity::NORMAL
      ));

      return;
    }
    // ... end of section to move.

    // To invalidate a suite throw an instance of \AKlump\CheckPages\Exceptions\BadSyntaxException.
    $this->getDispatcher()
      ->dispatch(new SuiteEvent($this->getSuite()), Event::SUITE_VALIDATION);

    // Add the tests to the suite in prep for the plugin hook...
    // If a suite file is empty, then just ignore it.  It's probably a stub file.
    if (!$suite->getConfig()) {
      return;
    }

    foreach ($suite->getConfig() as $test_config) {
      $suite->addTestByConfig($test_config);
    }

    $this->dispatcher->dispatch(new SuiteEvent($this->getSuite()), Event::SUITE_LOADED);

    // On return from the hook, we need to reparse to get format for validation.
    $this->validateSuite($suite, static::SCHEMA_VISIT . '.json');

    foreach ($suite->getTests() as $test) {
      $this->dispatcher->dispatch(new TestEvent($test), Event::TEST_VALIDATION);
      $this->dispatcher->dispatch(new TestEvent($test), Event::TEST_CREATED);

      // It's possible the test was already run during Event::TEST_CREATED, if
      // that handle has set the results, then the test should be considered
      // complete.
      if (!$test->hasFailed() && !$test->hasPassed()) {
        try {
          $test_runner = new TestRunner();
          $test_runner->run($test);
          $this->totalTestsRun++;
        }
        catch (TestFailedException $exception) {
          // We have to catch this here, because of the dispatching and decision
          // on what to do about it that is determined below looking at
          // configuration.
          $test->setFailed();
        }
      }

      if ($test->hasFailed()) {
        $this->dispatcher->dispatch(new TestEvent($test), Event::TEST_FAILED);
      }
      elseif ($test->hasPassed()) {
        $this->dispatcher->dispatch(new TestEvent($test), Event::TEST_PASSED);
      }

      $this->dispatcher->dispatch(new TestEvent($test), Event::TEST_FINISHED);

      // Decide if we should stop the runner or not.
      if ($test->hasFailed()) {
        $suite->setFailed();
        $this->failedTestCount++;
        if ($this->getConfig()['stop_on_failed_test'] ?? FALSE) {
          throw new TestFailedException($test->getConfig());
        }
      }
    }

    // Handle end-of-suite events.
    $suite_event = new SuiteEvent($this->getSuite());
    if ($suite->hasFailed()) {
      $this->dispatcher->dispatch($suite_event, Event::SUITE_FAILED);
      $this->failedSuiteCount++;
    }
    else {
      $this->dispatcher->dispatch($suite_event, Event::SUITE_PASSED);
    }
    $this->dispatcher->dispatch($suite_event, Event::SUITE_FINISHED);
    if ($suite->hasFailed() && ($this->getConfig()['stop_on_failed_suite'] ?? FALSE)) {
      throw new SuiteFailedException($suite->id());
    }
  }

  /**
   * @param \AKlump\CheckPages\Parts\Test $test
   * @param \AKlump\CheckPages\RequestDriverInterface $driver
   * @param $exception
   *
   * @return mixed
   * @throws \AKlump\CheckPages\Exceptions\TestFailedException
   */
  public function handleFailedRequestNoResponse(Test $test, RequestDriverInterface $driver, $exception) {
    // Try to be helpful with suggestions on mitigation of errors.
    $message = $exception->getMessage();

    $test->addMessage(new Message(
        [$message],
        MessageType::ERROR,
        Verbosity::DEBUG)
    );

    if (strstr($message, 'timed out') !== FALSE) {
      $test->addMessage(new Message(
          [
            sprintf('Try setting a value higher than %d for "request_timeout" in %s, or at the test level.', $driver->getRequestTimeout(), basename($this->configPath)),
          ],
          MessageType::ERROR,
          Verbosity::DEBUG)
      );
    }
    $test->setFailed();
    throw new TestFailedException($test->getConfig(), $exception);
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
   * Validate a suite for correct syntax.
   *
   * @param \AKlump\CheckPages\Parts\Suite $suite
   * @param string $schema_basename
   *
   * @return array
   */
  protected function validateSuite(Suite $suite, string $schema_basename): array {
    $suite_yaml = Yaml::dump($suite->jsonSerialize());
    $data = Yaml::parse($suite_yaml, YAML::PARSE_OBJECT_FOR_MAP);

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

      $directive = Verbosity::DEBUG;

      $this->echo(new Message([
        "Suite Group\\ID:",
        strval($suite),
        '',
      ], MessageType::ERROR, $directive), ConsoleEchoPrinter::INVERT_FIRST);

      $this->echo(new Message([
        "Test Configuration:",
      ], MessageType::ERROR, $directive), ConsoleEchoPrinter::INVERT_FIRST);

      $this->echo(new Message([
        json_encode($data, JSON_PRETTY_PRINT),
        '',
      ], MessageType::DEBUG, $directive));

      $this->echo(new Message([
        'Schema Path:',
        $path_to_schema,
        '',
      ], MessageType::ERROR, $directive), ConsoleEchoPrinter::INVERT_FIRST);

      $this->echo(new Message([
        "Schema Validation Errors:",
      ], MessageType::ERROR, $directive), ConsoleEchoPrinter::INVERT_FIRST);

      foreach ($validator->getErrors() as $error) {
        $this->echo(new Message([
          sprintf("[%s] %s", $error['property'], $error['message']),
          '',
        ], MessageType::DEBUG, $directive));
      }

      throw new \RuntimeException(sprintf('The suite (%s) does not match schema "%s". Use -vvv for more info.', $suite->id(), $schema_basename));
    }

    // Convert to arrays, we only needed objects for the validation.
    return json_decode(json_encode($data), TRUE);
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

    try {
      $storage_name = '';
      $subdir = '/storage/';
      $path_to_files = $this->getPathToFilesDirectory();
      if ($path_to_files) {
        $files = new Files($this);
        $path_to_storage = $files->prepareDirectory("$path_to_files/$subdir");
        if (is_dir($path_to_storage)) {
          $storage_name = pathinfo($this->runner['name'], PATHINFO_FILENAME);
          $storage_name = rtrim($path_to_storage, '/') . "/$storage_name";
        }
      }
    }
    catch (\Exception $exception) {
      $storage_name = '';
    }

    if (empty($storage_name)) {
      $this->echo(new Message([
        sprintf('To enable disk storage (i.e., sessions) create a writeable directory at %s.', rtrim($this->getConfig()['files'], '/') . $subdir),
      ], MessageType::TODO, Verbosity::VERBOSE | Verbosity::DEBUG));
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
      $config = $this->getConfig();
      if (empty($config['files'])) {
        $this->echo(new DebugMessage(['To enable file output you must set a value for "files" in your config.']));
        $this->pathToFiles = '';
      }
      else {
        try {
          $this->pathToFiles = $this->resolve($config['files']);
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
   * @param array $filenames
   *   Relative paths (or globs) to be deleted.
   *
   * @return void
   */
  public function deleteFiles(array $filenames): void {
    $path_to_files = $this->getPathToRunnerFilesDirectory();
    if (!$path_to_files) {
      return;
    }
    foreach ($filenames as $filename) {
      $files = glob(rtrim($path_to_files, '/') . '/' . ltrim($filename, '/'));
      foreach ($files as $file) {
        if ($path_to_files && strstr($file, $path_to_files) !== FALSE) {
          unlink($file);
        }
      }
    }
  }

  /**
   * Send data to a file in the files directory if it exists.
   *
   * @param string $filename
   *   A filename (or path) relative to the files directory for the destination.
   * @param array $lines
   *   The content to write to the file; elements are joined by \n.
   *
   * @return string The absolute filepath to the written file.
   */
  public function writeToFile(string $name, array $lines, string $mode = 'a+'
  ): string {
    $path_to_files = $this->getPathToRunnerFilesDirectory();
    if (!$path_to_files) {
      return '';
    }
    if (empty($this->writeToFileResources[$name])) {
      $path = [];
      $path[] = pathinfo($name, PATHINFO_FILENAME);
      $extension = pathinfo($name, PATHINFO_EXTENSION) ?? '';
      $extension = $extension ?: 'txt';
      $path = implode('-', $path) . ".$extension";

      $dirname = dirname($name);
      if ($dirname) {
        $path = "$dirname/$path";
        if (!is_dir($path_to_files . '/' . $dirname)) {
          mkdir($path_to_files . '/' . $dirname, 0755, TRUE);
        }
      }

      $handle = fopen($path_to_files . '/' . $path, $mode);
      $this->writeToFileResources[$name] = [
        $handle,
        $path_to_files . '/' . $path,
      ];
    }
    list($handle, $filepath) = $this->writeToFileResources[$name];
    fwrite($handle, implode(PHP_EOL, $lines) . PHP_EOL);

    return $filepath;
  }

  /**
   * Close out file resources if any.
   */
  public function __destruct() {
    $resources = $this->writeToFileResources ?? [];
    foreach ($resources as $info) {
      list($handle) = $info;
      fclose($handle);
    }
  }

}
