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

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::RUNNER_CONFIG_LOADED => [self::class, 'configureSuitesToIgnore'],
      Event::TEST_FINISHED => [self::class, 'writeTestResult'],
    ];
  }

  public static function configureSuitesToIgnore(RunnerEventInterface $event) {
    $input = $event->getRunner()->getInput();
    $is_using = $input->getOption('retest') || $input->getOption('continue');

    $tracking_path = self::getTrackingFilePath($event->getRunner());
    if (!$tracking_path) {
      if ($is_using) {
        $option = $input->getOption('retest') ? '--retest' : '--continue';
        $event->getRunner()
          ->getOutput()
          ->writeln(sprintf('<error>"%s" requires file storage to be enabled.  See documentation for more info.</error>', $option, $tracking_path));
      }

      return;
    }

    if (!$is_using) {

      if (!$input->getOption('filter') && !$input->getOption('group')) {
        // With no options, we need to set up a clean slate by truncated our
        // tracking file.  That way continue will work correctly.
        fclose(fopen($tracking_path, 'w'));
      }

      return;
    }

    $suites_to_ignore = [];
    $suites_to_run = [];
    $fp = fopen($tracking_path, 'r');
    while (($data = fgetcsv($fp))) {
      $id = [];
      $id[] = $data[0] ?? NULL;
      $id[] = $data[1] ?? NULL;
      $id = implode('/', array_filter($id));
      $result = $data[3] ?? NULL;
      if ($input->getOption('continue')) {
        $suites_to_ignore[$id] = $id;
      }
      elseif ($input->getOption('retest')) {
        if ($result === Test::PASSED) {
          $suites_to_ignore[$id] = $id;
        }
        elseif ($result == Test::FAILED) {
          $suites_to_run[$id] = $id;
        }
      }
    }
    fclose($fp);

    // We start with the last suite run, by repeating it.
    if ($input->getOption('continue')) {
      $start_with = array_pop($suites_to_ignore);
      $event->getRunner()
        ->getOutput()
        ->writeln(Color::wrap('white on blue', sprintf('...continuing where we left off with suite "%s".', $start_with)), OutputInterface::VERBOSITY_VERBOSE);
    }

    if ($suites_to_ignore) {
      $config = $event->getRunner()->getConfig();
      $config['suites_to_ignore'] = array_merge($config['suites_to_ignore'], array_values($suites_to_ignore));
      $config['suites_to_ignore'] = array_values(array_diff($config['suites_to_ignore'], array_values($suites_to_run)));
      $event->getRunner()->setConfig($config);
    }
  }

  public static function writeTestResult(DriverEventInterface $event) {
    $tracking_path = self::getTrackingFilePath($event->getTest()->getRunner());
    if (!$tracking_path || !file_exists($tracking_path)) {
      return;
    }
    $test = $event->getTest();

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
   * Return the full filepath the the CSV file.
   *
   * @param \AKlump\CheckPages\Parts\Runner $runner
   *
   * @return string
   */
  private static function getTrackingFilePath(Runner $runner): string {
    $tracking_path = $runner->getPathToRunnerFilesDirectory();
    if (empty($tracking_path)) {
      return '';
    }

    return "$tracking_path/_results.csv";
  }
}
