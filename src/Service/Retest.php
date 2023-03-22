<?php

namespace AKlump\CheckPages\Service;

use AKlump\CheckPages\Event\RunnerEventInterface;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Exceptions\StopRunnerException;
use AKlump\CheckPages\Files\FilesProviderInterface;
use AKlump\CheckPages\Output\Flags;
use AKlump\CheckPages\Output\Message;
use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\Traits\HasRunnerTrait;
use AKlump\Messaging\MessageType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use AKlump\CheckPages\Event;

/**
 * Provides the ability to repeat failed tests or continue from the last suite.
 */
final class Retest implements EventSubscriberInterface {

  use HasRunnerTrait;

  /**
   * Return the full filepath the the CSV file.
   *
   * @return string
   *   Absolute path to the results log file.
   */
  private function getFilepathToResults(): string {
    $log_files = $this->getRunner()->getLogFiles();
    $filepath = $log_files->tryResolveFile('results.csv', [], FilesProviderInterface::RESOLVE_NON_EXISTENT_PATHS)[0];
    $log_files->tryCreateDir(dirname($filepath));

    return $filepath;
  }

  private function readResults(): array {
    $filepath = $this->getFilepathToResults();
    $data = [];
    if (file_exists($filepath)) {
      $fp = fopen($filepath, 'r');
      while (($csv = fgetcsv($fp))) {
        list($datum['group'], $datum['suite'], , $datum['result']) = $csv;
        $data[] = $datum;
      }
      fclose($fp);
    }

    return $data;
  }

  private function writeTestResult(Test $test) {
    $filepath = $this->getFilepathToResults();
    if (!$filepath || !file_exists($filepath)) {
      return;
    }

    $fh = fopen($filepath, 'r+');
    $written = FALSE;
    $pointer = ftell($fh);
    while (($data = fgetcsv($fh))) {
      $group = $data[0] ?? NULL;
      $suite_id = $data[1] ?? NULL;
      $test_id = $data[2] ?? NULL;
      if ($group === $test->getSuite()
          ->getGroup() && $suite_id === $test->getSuite()
          ->id() && $test_id === $test->id()) {
        fseek($fh, $pointer);
        fputcsv($fh, [
          $test->getSuite()->getGroup(),
          $suite_id,
          $test_id,
          $test->hasFailed() ? Test::FAILED : Test::PASSED,
        ]);
        $written = TRUE;
        break;
      }
      $pointer = ftell($fh);
    }
    if (!$written) {
      fputcsv($fh, [
        $test->getSuite()->getGroup(),
        $test->getSuite()->id(),
        $test->id(),
        $test->hasFailed() ? Test::FAILED : Test::PASSED,
      ]);
    }
    fclose($fh);
  }

  /**
   * Run processes only on the first run.
   *
   * @return void
   */
  private function onFirstRun() {
    // Setup a clean slate if appropriate; empty the results file when
    // certain options are not being used.
    $options_being_used = array_keys(array_filter($this->getRunner()->getInput()
      ->getOptions()));

    if (!array_intersect($options_being_used, [
      'retest',
      'continue',
      'filter',
      'group',
    ])) {
      $filepath = $this->getFilepathToResults();
      if ($filepath) {
        // With no options, we need to set up a clean slate by truncated our
        // tracking file.  That way continue will work correctly.
        fclose(fopen($filepath, 'w'));
      }
    }

    // Handle messaging.
    if (in_array('continue', $options_being_used)) {
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

  private function filterByResult(array $results, string $test_result): array {
    return array_values(array_filter($results, function ($datum) use ($test_result) {
      return $datum['result'] === $test_result;
    }));
  }

  /**
   * Filter to group/suite results that have not a single failed test.
   *
   * @param array $results
   *
   * @return array
   *   The filtered set of totally passed group/suite $results.
   */
  private function getOnlyPassedSuites(array $results): array {
    $pass_results = $this->filterByResult($results, Test::PASSED);
    $fail_results = $this->filterByResult($results, Test::FAILED);
    $fail_results = $this->flattenResults($fail_results);

    return array_filter($pass_results, function (array $data) use ($fail_results) {
      $foo = $this->flattenResults([$data]);

      return !array_intersect($foo, $fail_results);
    });
  }

  private function getOnlyFullyCompletedSuites(array $results) {
    $most_recent = NULL;
    do {
      $foo = end($results);
      $a = $this->flattenResults([$foo]);
      if (!isset($most_recent)) {
        $most_recent = $a;
        continue;
      }
      array_pop($results);
    } while ($results && $a === $most_recent);

    return $results;

  }

  private function flattenResults(array $results): array {
    $results = array_map(function ($datum) {
      return ltrim($datum['group'] . '/' . $datum['suite'], '/');
    }, $results);

    return array_values(array_unique($results));
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::CONFIG_LOADED => [
        function (RunnerEventInterface $event) {
          static $suites_to_ignore;

          $runner = $event->getRunner();
          $retest = new self();
          $retest->setRunner($event->getRunner());

          if (!isset($suites_to_ignore)) {
            $suites_to_ignore = [];
            $input = $runner->getInput();
            $results = $retest->readResults();

            $is_retesting = $input->getOption('retest');
            $is_continuing = $input->getOption('continue');
            if ($is_retesting && $is_continuing) {
              throw new StopRunnerException("You may not combine --retest and --continue; pick one.");
            }
            elseif ($is_retesting) {
              $results = $retest->getOnlyPassedSuites($results);
            }
            elseif ($is_continuing) {
              $results = $retest->getOnlyFullyCompletedSuites($results);
            }
            else {
              $results = [];
            }
            $suites_to_ignore = $retest->flattenResults($results);
          }

          if ($suites_to_ignore) {
            $config = $runner->getConfig();
            $config['suites_to_ignore'] = array_values(array_unique(array_merge($config['suites_to_ignore'], $suites_to_ignore)));
            $runner->setConfig($config);
          }

          static $is_first_run;
          if (FALSE !== $is_first_run) {
            $retest->onFirstRun();
            $is_first_run = FALSE;
          }
        },
      ],
      Event::TEST_PASSED => [
        function (TestEventInterface $event) {
          $retest = new self();
          $retest
            ->setRunner($event->getTest()->getRunner())
            ->writeTestResult($event->getTest());
        },
      ],
      Event::TEST_FAILED => [
        function (TestEventInterface $event) {
          $retest = new self();
          $retest
            ->setRunner($event->getTest()->getRunner())
            ->writeTestResult($event->getTest());
        },
      ],
    ];
  }

}
