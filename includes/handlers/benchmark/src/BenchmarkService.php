<?php

namespace AKlump\CheckPages\Handlers\Benchmark;

use AKlump\CheckPages\Parts\Test;

class BenchmarkService {

  public function shouldProcessEvent(TestEventInterface $event): bool {
    $config = $event->getTest()->getConfig();
    if (empty($config['extras']['benchmark'])) {
      return FALSE;
    }

    return TRUE;
  }

  public function addBenchmarkDataToTest(Test $test) {
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
}
