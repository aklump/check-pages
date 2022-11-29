<?php

namespace AKlump\CheckPages\Service;

use AKlump\CheckPages\Event\RunnerEventInterface;
use AKlump\CheckPages\Output\ConsoleEchoPrinter;
use AKlump\CheckPages\Output\DebugMessage;
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

  private static $skipSuites;

  /**
   * Return the full filepath the the CSV file.
   *
   * @param \AKlump\CheckPages\Parts\Runner $runner
   *
   * @return string
   */
  public function getTrackingFilePath(): string {
    $tracking_path = $this->getRunner()->getPathToRunnerFilesDirectory();
    if (empty($tracking_path)) {
      $input = $this->getRunner()->getInput();
      $is_using = $input->getOption('retest') || $input->getOption('continue');
      if ($is_using) {
        $option = $input->getOption('retest') ? '--retest' : '--continue';
        $this->getRunner()->echo(new DebugMessage([
            sprintf('<error>"%s" requires file storage to be enabled.  See documentation for more info.</error>', $option),
          ]
        ));
      }

      return '';
    }

    return "$tracking_path/_results.csv";
  }

  /**
   * Get an array of group/suite to ignore based on $runner context.
   *
   * @return array;
   */
  public function getSuitesToIgnore(): array {
    $tracking_path = $this->getTrackingFilePath();
    $list = [];
    if (!$tracking_path) {
      return $list;
    }

    $input = $this->getRunner()->getInput();
    $retesting = $input->getOption('retest');
    $continuing = $input->getOption('continue');
    if ((!$retesting && !$continuing) || !file_exists($tracking_path)) {
      return $list;
    }

    $skip_if_continuing = [];
    $suites = [];
    $suites_with_failures = [];
    $fp = fopen($tracking_path, 'r');
    while (($data = fgetcsv($fp))) {
      list($g, $s, , $result) = $data;
      $cid = "$g/$s";
      $suites[$cid] = $cid;
      if (!in_array($cid, $skip_if_continuing)) {
        $skip_if_continuing[] = $cid;
      }
      if ($result !== Test::PASSED && !in_array($cid, $suites_with_failures)) {
        $suites_with_failures[] = $cid;
      }
    }

    if ($retesting) {
      $list = array_diff($suites, $suites_with_failures);
    }
    elseif ($continuing) {
      // We may not have completed the final suite listed, therefor we will
      // repeat it from the top.
      array_pop($skip_if_continuing);

      $list = $skip_if_continuing;
    }

    return array_values($list);
  }

  public function writeResults(Test $test) {
    $tracking_path = $this->getTrackingFilePath();
    if (!$tracking_path || !file_exists($tracking_path)) {
      return;
    }

    $fh = fopen($tracking_path, 'r+');
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
      $tracking_path = $this->getTrackingFilePath();
      if ($tracking_path) {
        // With no options, we need to set up a clean slate by truncated our
        // tracking file.  That way continue will work correctly.
        fclose(fopen($tracking_path, 'w'));
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
        ConsoleEchoPrinter::INVERT_FIRST
      );
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::RUNNER_CONFIG => [
        function (RunnerEventInterface $event) {
          $retest = new self();
          $runner = $event->getRunner();
          $retest->setRunner($runner);

          static $suites_to_ignore;
          $suites_to_ignore = $suites_to_ignore ?? $retest->getSuitesToIgnore();
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
        function (Event\TestEventInterface $event) {
          $retest = new self();
          $retest
            ->setRunner($event->getTest()->getRunner())
            ->writeResults($event->getTest());
        },
      ],
      Event::TEST_FAILED => [
        function (Event\TestEventInterface $event) {
          $retest = new self();
          $retest
            ->setRunner($event->getTest()->getRunner())
            ->writeResults($event->getTest());
        },
      ],
    ];
  }

}
