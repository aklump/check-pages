<?php

namespace AKlump\CheckPages\Service;

use AKlump\CheckPages\Event\DriverEventInterface;
use AKlump\CheckPages\Event\RunnerEventInterface;
use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Parts\Test;
use AKlump\LoftLib\Bash\Color;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use AKlump\CheckPages\Event;

/**
 * Provides the ability to repeat failed tests or continue from the last suite.
 */
final class Retest implements EventSubscriberInterface {

  private static $skipSuites;

  /**
   * @var \AKlump\CheckPages\Parts\Runner
   */
  private $runner;

  public function setRunner(Runner $runner): self {
    $this->runner = $runner;

    return $this;
  }

  /**
   * Return the full filepath the the CSV file.
   *
   * @param \AKlump\CheckPages\Parts\Runner $runner
   *
   * @return string
   */
  public function getTrackingFilePath(): string {
    $tracking_path = $this->runner->getPathToRunnerFilesDirectory();
    if (empty($tracking_path)) {
      $input = $this->runner->getInput();
      $is_using = $input->getOption('retest') || $input->getOption('continue');
      if ($is_using) {
        $option = $input->getOption('retest') ? '--retest' : '--continue';
        $this->runner->getOutput()
          ->writeln(sprintf('<error>"%s" requires file storage to be enabled.  See documentation for more info.</error>', $option, $tracking_path));
      }

      return '';
    }

    return "$tracking_path/_results.csv";
  }

  /**
   * Get an array of group/suite to ignore based on $runner context.
   *
   * @param \AKlump\CheckPages\Parts\Runner $runner
   *
   * @return array;
   */
  public function getSuitesToIgnore(): array {
    $tracking_path = $this->getTrackingFilePath();
    $list = [];
    if (!$tracking_path) {
      return $list;
    }

    $input = $this->runner->getInput();
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
      if (!$result && !in_array($cid, $suites_with_failures)) {
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
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::RUNNER_CONFIG_LOADED => [
        function (RunnerEventInterface $event) {
          $obj = new self();
          $obj->setRunner($event->getRunner());

          self::$skipSuites = self::$skipSuites ?? $obj->getSuitesToIgnore();
          if (self::$skipSuites) {
            $config = $event->getRunner()->getConfig();
            $config['suites_to_ignore'] = array_unique(array_merge($config['suites_to_ignore'], self::$skipSuites));
            $event->getRunner()->setConfig($config);
          }

          $input = $event->getRunner()->getInput();

          // Setup a clean slate if appropriate.
          if (!$input->getOption('retest')
            && !$input->getOption('continue')
            && !$input->getOption('filter')
            && !$input->getOption('group')) {
            $tracking_path = $obj->getTrackingFilePath();
            // With no options, we need to set up a clean slate by truncated our
            // tracking file.  That way continue will work correctly.
            fclose(fopen($tracking_path, 'w'));
          }

          // Handle messaging.
          if ($input->getOption('continue')) {
            $event->getRunner()
              ->getOutput()
              ->writeln(Color::wrap('white on blue', '...continuing with the first test of the last suite.'), OutputInterface::VERBOSITY_NORMAL);
          }
        },
      ],
      Event::TEST_PASSED => [
        function (Event\TestEventInterface $event) {
          $obj = new self();
          $obj
            ->setRunner($event->getTest()->getRunner())
            ->writeResults($event->getTest());
        },
      ],
      Event::TEST_FAILED => [
        function (Event\TestEventInterface $event) {
          $obj = new self();
          $obj
            ->setRunner($event->getTest()->getRunner())
            ->writeResults($event->getTest());
        },
      ],
    ];
  }

}
