<?php

namespace AKlump\CheckPages\Parts;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\RunnerEvent;
use AKlump\CheckPages\Event\SuiteEvent;
use AKlump\CheckPages\Exceptions\StopRunnerException;
use AKlump\CheckPages\Exceptions\SuiteFailedException;
use AKlump\CheckPages\Exceptions\TestFailedException;
use AKlump\CheckPages\Exceptions\UnresolvablePathException;
use AKlump\CheckPages\Files\FilesProviderInterface;
use AKlump\CheckPages\Files\LocalFilesProvider;
use AKlump\CheckPages\Helpers\FilterSuites;
use AKlump\CheckPages\Output\ConsoleEchoPrinter;
use AKlump\CheckPages\Output\DevNullPrinter;
use AKlump\CheckPages\Output\Flags;
use AKlump\CheckPages\Output\Icons;
use AKlump\CheckPages\Output\LoggerPrinter;
use AKlump\CheckPages\Output\Message;
use AKlump\CheckPages\Output\MultiPrinter;
use AKlump\CheckPages\Output\VerboseDirective;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\CheckPages\Service\DispatcherFactory;
use AKlump\CheckPages\Service\Retest;
use AKlump\CheckPages\Storage;
use AKlump\CheckPages\StorageInterface;
use AKlump\CheckPages\SuiteCollection;
use AKlump\CheckPages\Traits\BaseUrlTrait;
use AKlump\CheckPages\Traits\HasConfigTrait;
use AKlump\CheckPages\Traits\HasSuiteTrait;
use AKlump\CheckPages\Traits\PassFailTrait;
use AKlump\CheckPages\Traits\SerializationTrait;
use AKlump\CheckPages\Traits\SetTrait;
use AKlump\Messaging\HasMessagesInterface;
use AKlump\Messaging\HasMessagesTrait;
use AKlump\Messaging\MessageType;
use AKlump\Messaging\MessengerInterface;
use JsonSchema\Validator;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class Runner implements HasMessagesInterface {

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
  const PATH_TO_SCHEMA__SUITE = 'dist/schema.suite.json';

  const PATH_TO_SERVICE_DEFINITIONS = 'dist/services.yml';

  const OUTPUT_NORMAL = 1;

  const OUTPUT_QUIET = 2;

  const LOGGER_PRINTER_BASENAME = 'check_pages.log';

  static protected array $state;

  public static function getState(string $key) {
    return self::$state[$key] ?? NULL;
  }

  /**
   * Set a key/value that persists across all suites.
   *
   * @param string $key
   * @param $value
   *
   * @return void
   */
  public static function setState(string $key, $value): void {
    self::$state[$key] = $value;
  }

  /**
   * @var bool
   */
  private $runnerCreatedEventDispatched;

  public $totalAssertions = 0;

  public $failedAssertionCount = 0;

  public $skippedSuitesCount = 0;

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
  private bool $atLeastOneSuiteMadeItPastTheFilters = FALSE;


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
   * @var array|mixed
   */
  private $suitesToIgnore = [];

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
    $options = $input->getOptions() ?? [];
    $options += [
      'verbose' => FALSE,
      'debug' => FALSE,
      'show' => '',
    ];

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

  public function getTotalSkippedSuites(): int {
    return $this->skippedSuitesCount;
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
   * Add a filter to limit what is run.
   *
   * Multiple filters will use OR logic.
   *
   * @param string $filter
   *   "group/suite_id", "group/", or "/suite_id" or any regex.
   *
   * @return $this
   *   Self for chaining.
   */
  public function addFilter(string $filter): Runner {
    $filter = preg_replace('/\.ya?ml$/', '', $filter);
    $this->filters[] = $filter;

    return $this;
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
  public function applyFilters(SuiteCollection $suites): SuiteCollection {
    if (empty($this->filters)) {
      return $suites;
    }

    // Apply multiple using OR.
    $result = new SuiteCollection();
    foreach ($this->filters as $filter) {
      $result = $result->merge((new FilterSuites())($suites, $filter));
    }

    return $result;
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
      throw new RuntimeException(sprintf('The custom option "%s" has already been added to this runner.', $selector));
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

      if (count($this->filters) > 0 && !$this->atLeastOneSuiteMadeItPastTheFilters) {
        $this->echo(new Message([''], MessageType::INFO, Verbosity::VERBOSE));
        $this->getMessenger()->deliver(new Message(
          ['There are no suites that match at least one of your filters.'],
          MessageType::ERROR, Verbosity::NORMAL
        ));
        $filter_hints = [
          sprintf('%sReview %s', Icons::HINT, $runner_path),
          sprintf('%sThis might happen if you did not add the correct `run_suite()`:', Icons::HINT),
        ];
        foreach ($this->filters as $filter) {
          $filter_hints[] = sprintf('%se.g., run_suite("%s");', Icons::HINT, $filter);
        }
        $this->getMessenger()
          ->deliver(new Message($filter_hints, MessageType::INFO, Verbosity::VERBOSE
          ), Flags::INVERT_FIRST_LINE);
        $this->setFailed();
      }

      if ($this->failedTestCount) {
        $this->setFailed();
      }
      elseif (!$this->hasFailed()) {
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
   *   This will be used to check against filters
   *
   * @throws \AKlump\CheckPages\Exceptions\StopRunnerException
   * @throws \AKlump\CheckPages\Exceptions\SuiteFailedException
   * @throws \AKlump\CheckPages\Exceptions\TestFailedException
   *
   * @see run_suite()
   */
  public function run(Suite $suite, string $path_to_suite) {
    if (empty($this->files)) {
      throw new RuntimeException('Call setFiles() first.');
    }

    $result = $this->applyFilters(new SuiteCollection([$suite]));
    if ($result->count() == 0) {
      return;
    }
    $this->atLeastOneSuiteMadeItPastTheFilters = TRUE;

    // This may seem to be located in a strange place, but for a complex set of
    // reasons having to do with runner functions and test-writing architecture
    // this is the best choice for this event.  From the perspective of event
    // handlers this is going to be called the first time a suite is run, which
    // is roughly equal to the runner being created.  The actual Runner instance
    // is created sooner, but not effectively used until this point.
    if (empty($this->runnerCreatedEventDispatched)) {
      $log_files = $this->getLogFiles();
      if ($log_files) {
        $cache_filepath = $log_files->tryResolveDir('cache', FilesProviderInterface::RESOLVE_NON_EXISTENT_PATHS)[0];
        $this->getLogFiles()->tryCreateDir($cache_filepath);
      }

      $this->getDispatcher()
        ->dispatch(new RunnerEvent($this), Event::RUNNER_CREATED);
      $this->runnerCreatedEventDispatched = TRUE;
      $this->suitesToIgnore = $this->get('suites_to_ignore') ?? [];
    }

    // Allow the modification of the runner config before the suite is run.
    $this
      ->setSuite($suite)
      ->getDispatcher()
      ->dispatch(new RunnerEvent($this), Event::RUNNER_STARTED);

    // This section has to exist down here because the path resolution may have
    // changed since the RUNNER_CREATE event was first called, don't move up.
    $ignored_filepaths = [];
    foreach ($this->suitesToIgnore as $suite_to_ignore) {
      try {
        // We may have glob expansion.
        $ignored_filepaths = array_merge($ignored_filepaths, $this->files->tryResolveFile($suite_to_ignore, [
          'yaml',
          'yml',
        ]));
      }
      catch (UnresolvablePathException $exception) {

        // If we're asked to ignore a suite that can't be resolved, then that is
        // not an exception in this case, we can easily ignore it because we
        // can't find it.  Return NULL, which will be filtered out.
        continue;
      }
    }
    $ignored_filepaths = array_unique($ignored_filepaths);

    if ($path_to_suite && in_array($path_to_suite, $ignored_filepaths)) {
      $status = NULL;
      if ($this->getInput()->getOption(Retest::OPTION_RETEST)) {
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
      ->dispatch(new SuiteEvent($this->getSuite()), Event::SUITE_CREATED);

    // Add the tests to the suite in prep for the plugin hook...
    // If a suite file is empty, then just ignore it.  It's probably a stub file.
    if (!$suite->getTests()) {
      return;
    }

    $this->dispatcher->dispatch(new SuiteEvent($this->getSuite()), Event::SUITE_STARTED);

    // On return from the hook, we need to reparse to get format for validation.
    $this->validateSuite($suite, static::PATH_TO_SCHEMA__SUITE);

    // Do not change this to foreach as it will fail to run any tests that are
    // dynamically added during an earlier test.
    while (($test = $suite->getNextPendingTest())) {
      $test_runner = (new TestRunner($test))->start();

      // It's possible the test was already run during Event::TEST_CREATED, if
      // that handle has set the results, then the test should be considered
      // complete.
      if (!$test->hasFailed() && !$test->hasPassed()) {
        try {
          $test_runner->run();
        }
        catch (TestFailedException $exception) {
          // We have to catch this here, because of the dispatching and decision
          // on what to do about it that is determined below looking at
          // configuration.  Also we will add a error message so that writing
          // handlers is easier.
          $test->setFailed();
          foreach ($exception->getMessages() as $message) {
            $test->addMessage($message);
          }
          $test->addMessage(new Message([$exception->getMessage()], MessageType::ERROR));
        }
      }
      $runtime_test_result = $test_runner->processResult();
      $this->totalTestsRun++;

      if (FALSE === $runtime_test_result) {
        $this->failedTestCount++;
        if ($this->get('stop_on_failed_test') ?? FALSE) {
          throw new TestFailedException($test->getConfig(), NULL, $test->getMessages());
        }
      }

      if ($test->isSkipped()) {
        $this->skippedSuitesCount++;
        break;
      }
      foreach ($test->getMessages() as $message) {
        $this->echo($message);
      }
    }

    $runtime_suite_result = $this->processResult($suite);
    if (!$runtime_suite_result) {
      $this->failedSuiteCount++;
      if ($this->get('stop_on_failed_suite') ?? FALSE) {
        throw new SuiteFailedException($suite);
      }
    }
  }

  /**
   * Process the results of a suite at the end of it's lifecycle.
   *
   * @param \AKlump\CheckPages\Parts\Suite $suite
   *
   * @return bool
   *   True if the suite should be seen as successful.
   */
  private function processResult(Suite $suite): bool {

    // Assume the best in people.
    if (!$suite->hasFailed() && !$suite->isSkipped()) {
      $suite->setPassed();
    }

    $dispatcher = $this->dispatcher;

    if ($suite->hasPassed()) {
      $dispatcher->dispatch(new SuiteEvent($suite), Event::SUITE_PASSED);
    }
    elseif ($suite->isSkipped()) {
      $dispatcher->dispatch(new SuiteEvent($suite), Event::SUITE_SKIPPED);
    }
    else {
      $this->lastFailedSuite = $suite;
      $dispatcher->dispatch(new SuiteEvent($suite), Event::SUITE_FAILED);
    }

    $dispatcher->dispatch(new SuiteEvent($suite), Event::SUITE_FINISHED);

    return $suite->hasPassed() || $suite->isSkipped();
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
    $validation_checksum = '';
    $return_value = [];
    $generate_return_value = function ($data) use (&$validation_checksum, &$return_value) {
      $return_json = json_encode($data);
      $validation_checksum = md5($return_json);
      $return_value = json_decode($return_json, TRUE);
    };

    $data = $suite->jsonSerialize();
    $generate_return_value($data);

    $log_files = $this->getLogFiles();
    if ($log_files) {
      $validation_log_path = $log_files
                               ->tryResolveFile('cache/validated_suites.json', [], FilesProviderInterface::RESOLVE_NON_EXISTENT_PATHS)[0];

      $validated_suites = [];
      if (file_exists($validation_log_path)) {
        $validated_suites = json_decode(file_get_contents($validation_log_path), TRUE);
        $validated_suites = $validated_suites ?? [];
        $validation_lookup_map = array_column($validated_suites, 'checksum', 'id');
        $stored_checksum = $validation_lookup_map[$suite->toFilepath()] ?? NULL;
        if ($stored_checksum === $validation_checksum) {

          // The validation step in some suites can take a long time, by checksum
          // caching we can speed up test execution and only valid when the suite
          // changes.  As an example the test suite at the time of this writing
          // drops from 2.4 minutes down to 1.3 minutes.
          return $return_value;
        }
      }
    }

    // Do not validate $data properties that have been added using add_test_option().
    $path_to_schema = $this->files->tryResolveFile($schema_basename)[0];
    $schema = json_decode(file_get_contents($path_to_schema));

    // Dynamically add permissive items for every test option.  These have been
    // added on the fly and do not have schema support, as handlers do.
    foreach ($this->getRuntimeOptions() as $selector) {
      $schema->items->anyOf[] = (object) [
        'required' => [$selector],
        'additionalProperties' => TRUE,
      ];
    }

    $validator = new Validator();
    $suite_yaml = Yaml::dump($suite->jsonSerialize());
    $data = Yaml::parse($suite_yaml, YAML::PARSE_OBJECT_FOR_MAP);
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

      throw new RuntimeException(sprintf('The suite (%s) does not match schema "%s". Use -vvv for more info.', $suite->id(), $schema_basename));
    }

    // Convert to arrays, we only needed objects for the validation.
    $generate_return_value($data);
    if (isset($validation_log_path)) {
      $validated_suites[] = [
        'id' => $suite->toFilepath(),
        'checksum' => $validation_checksum,
      ];
      file_put_contents($validation_log_path, json_encode($validated_suites));
    }

    return $return_value;
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
  public function writeToFile(string $relative_path_to_file, array $lines, string $mode = 'a'): string {
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
