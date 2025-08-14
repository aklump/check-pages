<?php

namespace AKlump\CheckPages\EventSubscriber;

use AKlump\CheckPages\Collections\TestResult;
use AKlump\CheckPages\Collections\TestResultCollection;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\RunnerEventInterface;
use AKlump\CheckPages\Event\SuiteEventInterface;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Exceptions\BadSyntaxException;
use AKlump\CheckPages\Exceptions\StopRunnerException;
use AKlump\CheckPages\Files\FilesProviderInterface;
use AKlump\CheckPages\Helpers\GetResultCodePerExpectations;
use AKlump\CheckPages\Interfaces\ProvidesInputOptionsInterface;
use AKlump\CheckPages\Output\Flags;
use AKlump\CheckPages\Output\Message\Message;
use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\Service\TestResultCollectionStorage;
use AKlump\CheckPages\Traits\HasRunnerTrait;
use AKlump\Messaging\MessageType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides the ability to repeat failed tests or continue from the last suite.
 */
final class Retest implements EventSubscriberInterface, ProvidesInputOptionsInterface {

  use HasRunnerTrait;

  const OPTION_RETEST = 'retest';

  const OPTION_CONTINUE = 'continue';

  const MODE_RETEST = 1;

  const MODE_CONTINUE = 2;

  private bool $enabled = FALSE;

  private bool $suiteHasBeenMarkedAsPending = FALSE;

  public static function getSubscribedEvents(): array {
    $retest = new Retest();

    return [
      Event::RUNNER_CREATED => [$retest, 'handleRunnerCreated'],
      Event::SUITE_CREATED => [$retest, 'handleSuiteCreated'],
      Event::TEST_CREATED => [$retest, 'handleTestCreated'],
      Event::TEST_FINISHED => [$retest, 'handleTestResult'],
    ];
  }

  public function handleRunnerCreated(RunnerEventInterface $event) {
    $this->setRunner($event->getRunner());
    $retest_options = $this->tryGetRetestOptions();

    // Variable is checked by the other event listeners that came after this one
    // to know if they should run or not.
    $this->enabled = $this->tryCheckRequirements($retest_options);
    if (!$this->enabled) {
      return;
    }

    // This must always run, regardless of options because this will
    // initialize the results log based on the options being used.
    $this->prepareFiles();

    if ($retest_options & (Retest::MODE_RETEST | Retest::MODE_CONTINUE)
      && ($collection = $this->loadResultsFromStorage())) {
      $suites_to_ignore = $this->getSuitesToIgnore($collection, $retest_options);
      if ($suites_to_ignore) {
        $runner = $this->getRunner();
        $config = $runner->getConfig();
        $config['suites_to_ignore'] = array_values(array_unique(array_merge($config['suites_to_ignore'] ?? [], $suites_to_ignore)));
        $runner->setConfig($config);
      }
    }
  }

  public function handleSuiteCreated(SuiteEventInterface $event) {
    $this->suiteHasBeenMarkedAsPending = FALSE;
  }

  public function handleTestCreated(TestEventInterface $event) {
    if (!$this->enabled) {
      return;
    }
    if (!$this->suiteHasBeenMarkedAsPending) {
      $this->markTestResultsAsPendingBySuite($event->getTest()->getSuite());
      $this->suiteHasBeenMarkedAsPending = TRUE;
    }
  }

  public function handleTestResult(TestEventInterface $event) {
    if ($this->enabled) {
      $this->registerTestResult($event->getTest());
    }
  }

  /**
   * When a suite starts, all it's tests must be marked as pending as an
   * exception may cause the suite not to finish.  The next time an option
   * --retest or --continue is used, the starting point will be miscalculated.
   * That is, if we don't do the following.  The failed suite will be skipped on
   * the very next --retest, which is a big problem that drops failures through
   * the cracks.
   *
   * @param \AKlump\CheckPages\Parts\Suite $suite
   *
   * @return void
   */
  private function markTestResultsAsPendingBySuite(Suite $suite) {
    $results = $this->loadResultsFromStorage() ?? new TestResultCollection();
    $results = $results->filter(function (TestResult $result) use ($suite) {
      return $this->getFilterKey($result) !== $this->getFilterKey($suite);
    });
    foreach ($suite->getTests() as $test) {
      $pending_result = new TestResult();
      $pending_result
        ->setGroupId($suite->getGroup())
        ->setSuiteId($suite->id())
        ->setTestId($test->id())
        ->setResult(Test::PENDING);
      $results->add($pending_result);
    }
    $filepath = $this->getPathToResultsLog();
    (new TestResultCollectionStorage())->save($filepath, $results);
  }

  public function registerTestResult(Test $test) {
    $result_code_per_expectations = (new GetResultCodePerExpectations())($test);
    $suite = $test->getSuite();
    $test_result = new TestResult();
    $test_result
      ->setGroupId($suite->getGroup())
      ->setSuiteId($suite->id())
      ->setTestId($test->id())
      ->setResult($result_code_per_expectations);
    $storage_service = new TestResultCollectionStorage();
    $filepath = $this->getPathToResultsLog();
    $collection = $storage_service->load($filepath) ?? new TestResultCollection();
    $is_changed = $collection->add($test_result);
    if ($is_changed) {
      $storage_service->save($filepath, $collection);
    }
  }

  /**
   * Return the full filepath the the CSV file.
   *
   * @return string
   *   Absolute path to the results log file.
   */
  private function getPathToResultsLog(): string {
    $log_files = $this->getRunner()->getLogFiles();
    $filepath = $log_files->tryResolveFile('results.csv', [], FilesProviderInterface::RESOLVE_NON_EXISTENT_PATHS)[0];
    $log_files->tryCreateDir(dirname($filepath));

    return $filepath;
  }

  /**
   * Load the test results from the storage.
   *
   * @return TestResultCollection|null
   *   The loaded test results from the storage, or null if nothing stored.
   */
  private function loadResultsFromStorage(): ?TestResultCollection {
    $filepath = $this->getPathToResultsLog();

    return (new TestResultCollectionStorage())->load($filepath);
  }

  /**
   * Prepare the files based on CLI options.
   *
   * @return void
   */
  private function prepareFiles() {
    // Setup a clean slate if appropriate; empty the results file when
    // certain options are not being used.
    $options_being_used = array_keys(array_filter($this->getRunner()->getInput()
      ->getOptions()));

    if (!array_intersect($options_being_used, [
      Retest::OPTION_RETEST,
      Retest::OPTION_CONTINUE,
      'filter',
      'group',
    ])) {
      $filepath = $this->getPathToResultsLog();
      // With no options, we need to set up a clean slate by truncated our
      // tracking file.  That way "--continue" will work correctly.
      (new TestResultCollectionStorage())->save($filepath, new TestResultCollection());
    }

    // Handle messaging.
    if (in_array(Retest::OPTION_CONTINUE, $options_being_used)) {
      $this->getRunner()->echo(new Message(
        [
          '...continuing with the first test of the last suite.',
        ],
        MessageType::INFO
      ),
        Flags::INVERT_FIRST_LINE
      );
    }
  }

  /**
   * @param int $retest_options
   *
   * @return string[]
   */
  public function getSuitesToIgnore(TestResultCollection $collection, int $retest_options): array {
    if ($retest_options & self::MODE_RETEST) {
      $tests_to_run = $collection->withoutPassedTests();
    }
    elseif ($retest_options & self::MODE_CONTINUE) {
      $tests_to_run = $collection->withoutCompletedTests();
    }
    else {
      return [];
    }

    // Get a unique list of suite ids to be run.
    $suites_to_run_by_id = array_values(array_unique(array_map(function (TestResult $result) {
      return $this->getFilterKey($result);
    }, $tests_to_run->toArray())));

    $tests_to_ignore = $collection->filter(function (TestResult $result) use ($suites_to_run_by_id) {
      // We will ignore the test if it's suite is not one we want.
      return !in_array($this->getFilterKey($result), $suites_to_run_by_id);
    });

    // Convert the test results to filter strings and ensure they are unique.
    $ignore_filter_strings = array_map(function (TestResult $result) {
      // Given the results, we now will return the formatted strings.
      return $this->getFilterKey($result);
    }, $tests_to_ignore->toArray());

    return array_values(array_unique($ignore_filter_strings));
  }

  /**
   * @param int $retest_options
   *
   * @return bool
   * @throws \AKlump\CheckPages\Exceptions\StopRunnerException
   */
  private function tryCheckRequirements(int $retest_options): bool {
    $requirements_met = (bool) $this->getPathToResultsLog();

    // Warn the user who uses an option that REQUIRES that we are able to
    // write log files, by stopping the runner.
    if (!$requirements_met && ($retest_options & Retest::MODE_RETEST || $retest_options & Retest::MODE_CONTINUE)) {
      $retest_options = $retest_options & Retest::MODE_CONTINUE ? Retest::OPTION_CONTINUE : Retest::OPTION_RETEST;
      throw new StopRunnerException(sprintf("--%s failed because log files are not configured correctly.", $retest_options));
    }

    return $requirements_met;
  }

  private function tryGetRetestOptions(): int {
    $input = $this->getRunner()->getInput();
    $retest_options = $input->getOption(Retest::OPTION_RETEST) ? Retest::MODE_RETEST : 0;
    $retest_options |= $input->getOption(Retest::OPTION_CONTINUE) ? Retest::MODE_CONTINUE : 0;

    // Do not allow combining options.
    if ($retest_options & Retest::MODE_RETEST & Retest::MODE_CONTINUE) {
      throw new BadSyntaxException("You may not combine --retest and --continue; pick one.");
    }

    return $retest_options;
  }

  public function getInputOptions(): array {
    return [
      Retest::OPTION_RETEST,
      Retest::OPTION_CONTINUE,
    ];
  }

  private function getFilterKey($object) {
    if ($object instanceof TestResult) {
      $key = $object->getGroupId() . '/' . $object->getSuiteId();
    }
    elseif ($object instanceof Suite) {
      $key = $object->getGroup() . '/' . $object->id();
    }
    else {
      throw new \RuntimeException('Unknown object type.');
    }

    return ltrim($key, '/');
  }
}
