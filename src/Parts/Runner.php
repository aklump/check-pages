<?php

namespace AKlump\CheckPages\Parts;

use AKlump\CheckPages\Browser\RequestDriverInterface;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\RunnerEvent;
use AKlump\CheckPages\Event\SuiteEvent;
use AKlump\CheckPages\Event\TestEvent;
use AKlump\CheckPages\Exceptions\StopRunnerException;
use AKlump\CheckPages\Exceptions\SuiteFailedException;
use AKlump\CheckPages\Exceptions\TestFailedException;
use AKlump\CheckPages\Exceptions\UnresolvablePathException;
use AKlump\CheckPages\Files\FilesProviderInterface;
use AKlump\CheckPages\Files\LocalFilesProvider;
use AKlump\CheckPages\Output\ConsoleEchoPrinter;
use AKlump\CheckPages\Output\DebugMessage;
use AKlump\CheckPages\Output\DevNullPrinter;
use AKlump\CheckPages\Output\Flags;
use AKlump\CheckPages\Output\LoggerPrinter;
use AKlump\CheckPages\Output\Message;
use AKlump\CheckPages\Output\MultiPrinter;
use AKlump\CheckPages\Output\VerboseDirective;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\CheckPages\SerializationTrait;
use AKlump\CheckPages\Service\DispatcherFactory;
use AKlump\CheckPages\Storage;
use AKlump\CheckPages\StorageInterface;
use AKlump\CheckPages\Traits\BaseUrlTrait;
use AKlump\CheckPages\Traits\HasSuiteTrait;
use AKlump\CheckPages\Traits\PassFailTrait;
use AKlump\CheckPages\Traits\SetTrait;
use AKlump\Messaging\HasMessagesTrait;
use AKlump\Messaging\MessageType;
use AKlump\Messaging\MessengerInterface;
use JsonSchema\Validator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use AKlump\CheckPages\Traits\HasConfigTrait;

class Runner {

  use PassFailTrait;
  use HasSuiteTrait;
  use SerializationTrait;
  use SetTrait;
  use HasMessagesTrait;
  use BaseUrlTrait;
  use HasConfigTrait {
    HasConfigTrait::setConfig as traitSetConfig;
  }

  /**
   * The filename without extension or path.
   *
   * @var string
   */
  const SCHEMA_VISIT = 'schema.suite.DO_NOT_EDIT';

  const OUTPUT_NORMAL = 1;

  const OUTPUT_QUIET = 2;

  const LOGGER_PRINTER_BASENAME = 'debug.log';

  public $totalAssertions = 0;

  public $failedAssertionCount = 0;

  /**
   * @var string
   */
  private $id;

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
  protected $runtimeOptions = [];

  /**
   * @var array
   */
  protected $writeToFileResources;

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
   * @var FilesProviderInterface
   */
  private $logFiles;

  private $input;

  private $output;

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  private $dispatcher;

  /**
   * @var \AKlump\CheckPages\Files\LocalFilesProvider
   */
  private $files;

  /**
   * @var \AKlump\CheckPages\Parts\Suite
   */
  private $lastFailedSuite;

  /**
   * App constructor.
   *
   * @param string $root_dir
   *   The system path to this test app root directory.  The schema files, for
   *   example are found in this directory.
   */
  public function __construct(InputInterface $input, OutputInterface $output) {
    $this->input = $input;
    $this->output = $output;
    $this->outputMode = Runner::OUTPUT_NORMAL;
    if ($this->getOutput()->isQuiet()) {
      $this->outputMode = Runner::OUTPUT_QUIET;
    }
  }

  /**
   * @param \AKlump\CheckPages\Files\FilesProviderInterface $files ;
   *
   * @return self
   *   Self for chaining.
   */
  public function setFiles(FilesProviderInterface $files): self {
    $this->files = $files;

    return $this;
  }

  public function getFiles(): ?FilesProviderInterface {
    return $this->files;
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

    $printers = [];
    $printers[] = new ConsoleEchoPrinter($output, $flags);
    $printers[] = new LoggerPrinter(self::LOGGER_PRINTER_BASENAME, $this);

    return new MultiPrinter($printers);
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
      global $container;
      if ($container) {
        $this->dispatcher = DispatcherFactory::createFromContainer($container);
      }
      else {
        $this->dispatcher = DispatcherFactory::create();
      }
    }

    return $this->dispatcher;
  }

  /**
   * @return string
   *   The id or name of the runner.
   */
  public function id(): string {
    return $this->id;
  }

  /**
   * @param string $id
   *
   * @return
   *   Self for chaining.
   */
  public function setId(string $id): self {
    $this->id = $id;

    return $this;
  }

  /**
   * @return string
   *   The path to the currently loaded config.
   */
  public function getLoadedConfigPath(): string {
    return $this->configPath ?? '';
  }

  public function loadConfig(string $resolve_config_path) {
    $config_path = $this->files->tryResolveFile($resolve_config_path, [
      'yaml',
      'yml',
    ])[0];
    try {
      $config = Yaml::parseFile($config_path);
      if (!$config || !is_array($config)) {
        throw new ParseException('');
      }
    }
    catch (ParseException $exception) {
      throw new StopRunnerException(sprintf('Failed to load configuration from "%s" due to a parse error.', $resolve_config_path), 0, $exception);
    }
    $this->setConfig($config);
    $this->configPath = $config_path;

    $this->getDispatcher()
      ->dispatch(new RunnerEvent($this), Event::CONFIG_LOADED);

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
    $this->traitSetConfig($config);
    if (isset($config['base_url'])) {
      $this->setBaseUrl($config['base_url']);
    }

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
  public function addSuiteIdFilter(string $filter): Runner {
    if (preg_match('/\.ya?ml$/', $filter)) {
      $pathinfo = pathinfo($filter);
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

  /**
   * @return \AKlump\CheckPages\Plugin\src\Option[]
   */
  public function getRuntimeOptions(): array {
    return $this->runtimeOptions;
  }

  /**
   * Add a runtime option name.
   *
   * @param string $selector
   *   The runtime selector, which must not have been added yet.
   *
   * @return void
   *
   * @throws \RuntimeException If $selector has previously been added.
   */
  public function tryAddRuntimeOption(string $selector) {
    if (in_array($selector, $this->runtimeOptions)) {
      throw new \RuntimeException(sprintf('The custom option "%s" has already been added to this runner.', $selector));
    }
    $this->runtimeOptions[] = $selector;
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
  public function executeRunner(string $runner_path) {
    try {
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
        $this->echo(new Message([''], MessageType::INFO, Verbosity::VERBOSE));
        $this->getMessenger()->deliver(new Message(
          [
            basename($runner_path),
            sprintf('There are no suites in %s that match at least one of your filters.', basename($runner_path)),
            'This can happen if you have not added `run_suite()` with a path to the intended suite(s).',
            '',
          ],
          MessageType::INFO, Verbosity::VERBOSE
        ), Flags::INVERT_FIRST_LINE);
      }

      if ($this->failedTestCount) {
        $this->setFailed();
      }
      else {
        $this->setPassed();
      }
    }
    catch (StopRunnerException $exception) {
      $this->setFailed();
    }

    // TODO I'm not sure this should be called when an exception is thrown.
    $this->getDispatcher()
      ->dispatch(new RunnerEvent($this), Event::RUNNER_FINISHED);

    if (!empty($exception)) {
      throw $exception;
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
      ->dispatch(new RunnerEvent($this), Event::RUNNER_STARTED);

    // TODO Might be worth moving this out of this method to src/SuiteValidator.php...
    $ignored_suite_paths = array_filter(array_map(function ($suite_to_ignore) {
      try {
        return $this->files->tryResolveFile($suite_to_ignore, [
          'yaml',
          'yml',
        ])[0];
      }
      catch (UnresolvablePathException $exception) {

        // If we're asked to ignore a suite that can't be resolved, then that is
        // not an exception in this case, we can easily ignore it because we
        // can't find it.  Return NULL, which will be filtered out.
        return NULL;
      }
    }, $this->get('suites_to_ignore') ?? []));

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
    unset($test_config);

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
          // configuration.  Also we will add a error message so that writing
          // plugins is easier.
          $test->setFailed();
          $test
            ->addMessage(new Message([$exception->getMessage()], MessageType::ERROR));
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
        $this->lastFailedSuite = $suite;
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
   * @param \AKlump\CheckPages\Browser\RequestDriverInterface $driver
   * @param $exception
   *
   * @return mixed
   * @throws \AKlump\CheckPages\Exceptions\TestFailedException
   */
  public function handleFailedRequestNoResponse(Test $test, RequestDriverInterface $driver, $exception) {
    // Try to be helpful with suggestions on mitigation of errors.
    $message = $exception->getMessage();
    if (strstr($message, 'timed out') !== FALSE) {
      $test->addMessage(new DebugMessage(
          [
            sprintf('Try setting a value higher than %d for "request_timeout" in %s, or at the test level.', $driver->getRequestTimeout(), basename($this->configPath)),
          ],
          MessageType::TODO)
      );
    }
    $test->setFailed();

    throw new TestFailedException($test->getConfig(), $exception);
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
    $path_to_schema = $this->files->tryResolveFile($schema_basename)[0];
    $schema = json_decode(file_get_contents($path_to_schema));

    // Dynamically add permissive items for every test option.  These have been
    // added on the fly and do not have schema support, as plugins do.
    foreach ($this->getRuntimeOptions() as $selector) {
      $schema->items->anyOf[] = (object) [
        'required' => [$selector],
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
      ], MessageType::ERROR, $directive), Flags::INVERT_FIRST_LINE);

      $this->echo(new Message([
        "Test Configuration:",
      ], MessageType::ERROR, $directive), Flags::INVERT_FIRST_LINE);

      $this->echo(new Message([
        json_encode($data, JSON_PRETTY_PRINT),
        '',
      ], MessageType::DEBUG, $directive));

      $this->echo(new Message([
        'Schema Path:',
        $path_to_schema,
        '',
      ], MessageType::ERROR, $directive), Flags::INVERT_FIRST_LINE);

      $this->echo(new Message([
        "Schema Validation Errors:",
      ], MessageType::ERROR, $directive), Flags::INVERT_FIRST_LINE);

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
    $this->storage = new Storage($this->getLogFiles());

    return $this->storage;
  }

  /**
   * Return a files provider for writing log files.
   *
   * @return \AKlump\CheckPages\Files\FilesProviderInterface
   *   An instance for writing files.
   */
  public function getLogFiles(): ?FilesProviderInterface {
    if (NULL === $this->logFiles) {
      if (!$this->has('files')) {
        // This happens if this gets called before config is loaded; in such
        // case we do not want to alter $this->logFiles yet; the hope is to
        // catch it the next time--after config has been loaded.
        return NULL;
      }
      try {
        $log_files_path = $this->get('files') . '/' . $this;
        $log_files_path = $this->files
                            ->tryCreateDir($log_files_path)
                            ->tryResolveDir($log_files_path)[0];
        $this->logFiles = new LocalFilesProvider($log_files_path);
      }
      catch (\InvalidArgumentException $exception) {
        $this->logFiles = NULL;
      }
    }

    return $this->logFiles;
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
  public function writeToFile(string $relative_path_to_file, array $lines, string $mode = 'a+'): string {
    if (!$this->getLogFiles()) {
      return '';
    }

    // Keep track of our open resources, opening only once.
    if (empty($this->writeToFileResources[$relative_path_to_file])) {
      $absolute_path = $this->getLogFiles()
                         ->tryCreateDir(dirname($relative_path_to_file))
                         ->tryResolveFile($relative_path_to_file, [], FilesProviderInterface::RESOLVE_NON_EXISTENT_PATHS)[0];
      $handle = fopen($absolute_path, $mode);
      $this->writeToFileResources[$relative_path_to_file] = [
        $handle,
        $absolute_path,
      ];
    }

    list($handle, $filepath) = $this->writeToFileResources[$relative_path_to_file];
    fwrite($handle, implode(PHP_EOL, $lines) . PHP_EOL);

    return $filepath;
  }

  public function __toString() {
    return $this->id();
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

  public function getLastFailedSuite(): ?Suite {
    return $this->lastFailedSuite ?? NULL;
  }
}
