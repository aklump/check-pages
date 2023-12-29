<?php

namespace AKlump\CheckPages\Service;

use AKlump\CheckPages\Collections\TestResult;
use AKlump\CheckPages\Collections\TestResultCollection;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\RunnerEventInterface;
use AKlump\CheckPages\Event\SuiteEventInterface;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Exceptions\BadSyntaxException;
use AKlump\CheckPages\Exceptions\StopRunnerException;
use AKlump\CheckPages\Files\FilesProviderInterface;
use AKlump\CheckPages\Output\Flags;
use AKlump\CheckPages\Output\Message;
use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\Traits\HasRunnerTrait;
use AKlump\Messaging\MessageType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides the ability to repeat failed tests or continue from the last suite.
 */
final class Retest implements EventSubscriberInterface, \AKlump\CheckPages\Interfaces\ProvidesInputOptionsInterface {

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

    if ($retest_options & (Retest::MODE_RETEST | Retest::MODE_CONTINUE)) {
      $this->processSuitesToIgnore($retest_options);
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
   * Adds config "suites_to_ignore" based on options and result state.
   *
   * @param int $retest_options The retest options.
   *
   * @return void
   */
  private function processSuitesToIgnore(int $retest_options): void {
    $suitesToIgnore = $this->getSuitesToIgnore($retest_options);
    if ($suitesToIgnore) {
      $runner = $this->getRunner();
      $config = $runner->getConfig();
      $config['suites_to_ignore'] = array_values(array_unique(array_merge($config['suites_to_ignore'] ?? [], $suitesToIgnore)));
      $runner->setConfig($config);
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
    $collection = $this->loadResultsFromStorage() ?? new TestResultCollection();
    $should_save = FALSE;
    foreach ($suite->getTests() as $test) {
      $presumed_failure = new TestResult();
      $presumed_failure
        ->setGroupId($suite->getGroup())
        ->setSuiteId($suite->id())
        ->setTestId($test->id())
        ->setResult(Test::PENDING);
      $is_changed = $collection->add($presumed_failure);
      $should_save = $should_save ?: $is_changed;
    }
    if ($should_save) {
      $filepath = $this->getPathToResultsLog();
      $storage_service = new TestResultCollectionStorage();
      $storage_service->save($filepath, $collection);
    }
  }

  public function registerTestResult(Test $test) {
    $result_code = $test->hasPassed() ? Test::PASSED : NULL;
    if (!$result_code) {
      $result_code = $test->hasFailed() ? Test::FAILED : NULL;
    }
    if(!$result_code) {
      $result_code = $test->isSkipped() ? Test::SKIPPED : NULL;
    }
    $result_code = $result_code ?? Test::PENDING;

    $suite = $test->getSuite();
    $test_result = new TestResult();
    $test_result
      ->setGroupId($suite->getGroup())
      ->setSuiteId($suite->id())
      ->setTestId($test->id())
      ->setResult($result_code);
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

  public function getSuitesToIgnore(int $retest_options) {
    $collection = $this->loadResultsFromStorage();
    if (!$collection) {
      return [];
    }
    if ($retest_options & self::MODE_RETEST) {
      $collection = $collection->filterPassedSuites();
    }
    elseif ($retest_options & self::MODE_CONTINUE) {
      $collection = $collection->filterCompletedSuites();
    }

    return array_map(function (TestResult $test_result) {
      return ltrim($test_result->getGroupId() . '/' . $test_result->getSuiteId(), '/');
    }, $collection->toArray());
  }

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
}
