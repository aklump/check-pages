<?php

namespace AKlump\CheckPages\Handlers;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\DriverEventInterface;
use AKlump\CheckPages\Output\Message;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\CheckPages\Parts\Test;
use AKlump\Messaging\MessageType;

class Benchmark implements HandlerInterface {

  public static function getId(): string {
    return 'benchmark';
  }

  public static function addBenchmarkingDataToTest(Test $test) {
    $config = $test->getConfig();
    $total_times = $config['benchmark']['times'] ?? 1;
    $tries = [];
    for ($i = 1; $i <= $total_times; $i++) {
      $try_config = $config;
      $try_config['extras']['benchmark'] = [
        'current' => $i,
        'total_times' => $total_times,
      ];
      $tries[] = $try_config;
    }
    $test->getSuite()->replaceTestWithMultiple($test, $tries);
  }

  public static function getSubscribedEvents() {
    return [
      Event::SUITE_CREATED => [
        function (Event\SuiteEventInterface $event) {
          foreach ($event->getSuite()->getTests() as $test) {
            $config = $test->getConfig();
            if (!empty($config['benchmark'])) {
              self::addBenchmarkingDataToTest($test);
            }
          }
        },
      ],
      Event::REQUEST_STARTED => [
        function (DriverEventInterface $event) {
          $config = $event->getTest()->getConfig();
          if (empty($config['extras']['benchmark'])) {
            return;
          }
          $data = &$config['extras']['benchmark'];

          // Store start microtime for the first time.
          $start = microtime(TRUE);
          if ($data['current'] === 1) {
            $event->getTest()
              ->getSuite()
              ->variables()
              ->setItem('benchmark.start', $start);
          }
          $data['started'] = $start;
          $event->getTest()->setConfig($config);

          $event->getTest()->addMessage(new Message([
            '🟢 ' . $start,
          ], MessageType::DEBUG, Verbosity::DEBUG));

          unset($data);
        },
      ],
      Event::REQUEST_FINISHED => [
        function (DriverEventInterface $event) {
          $test = $event->getTest();
          $config = $test->getConfig();
          if (empty($config['extras']['benchmark'])) {
            return;
          }
          $data = &$config['extras']['benchmark'];
          $finished = microtime(TRUE);

          $this_elapsed = $finished - $data['started'];
          $event->getTest()->addMessage(new Message([
            '🔴 ' . sprintf('%s - ELAPSED %s', $finished, self::formatseconds($this_elapsed)),
          ], messagetype::DEBUG, verbosity::DEBUG));

          $event->getTest()->setConfig($config);

          if ($data['current'] < $data['total_times']) {
            return;
          }

          $start = $event->getTest()
            ->getSuite()
            ->variables()
            ->getItem('benchmark.start');
          $elapsed_seconds = $finished - $start;

          // Calculate the average.
          if ($data['total_times'] > 1) {
            $elapsed_seconds /= $data['total_times'];
          }

          $formated_time = self::formatSeconds($elapsed_seconds);

          // Handle messaging
          $default_ask = 'Benchmark: %s';
          $ask = $config["benchmark"]["ask"] ?? $default_ask;
          $test->getSuite()->interpolate($ask);

          $average = sprintf('%s', $formated_time);
          if ($data['total_times'] > 1) {
            $average = sprintf('%s on average over %d times.', $formated_time, $data['total_times']);
          }

          $test->addMessage(new Message([
            NULL,
            '🤔 ' . $ask,
            '👉 ' . $average,
            NULL,
          ], MessageType::INFO, Verbosity::NORMAL));

          unset($data);
        },
      ],
    ];
  }

  private static function formatSeconds($elapsed_seconds) {
    if ($elapsed_seconds < 1) {
      return sprintf('%.3f', $elapsed_seconds * 1000) . ' ms';
    }

    return sprintf('%.3f', $elapsed_seconds) . ' s';
  }

}
