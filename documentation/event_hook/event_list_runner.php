<?php

/**
 * @file
 *
 * Compile the available event data into a JSON file.
 */

/** @var \AKlump\LoftDocs\Compiler $compiler */

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\RunnerEvent;

// You must make sure these are set to the extreme event names otherwise the
// documentation will be incorrect.
$script_control = [
  'first_event' => Event::RUNNER_CREATED,
  'last_event' => Event::RUNNER_FINISHED,
];
//
// End configuration.
//

/**
 * Note: This must be the earliest running event to work correctly.
 */
respond_to([
  $script_control['first_event'],
  // This has to run before everything else.
  10000,
], function ($event, string $first_event) use (&$script_control) {
  $runner = $event->getRunner();
  $config = $runner->getConfig();
  unset($config['files']);
  $runner->setConfig($config);
  $event_list = [
    $first_event => [
      'order' => 1,
      'event' => $first_event,
      'class' => get_class($event),
    ],
  ];

  $dispatcher = $runner->getDispatcher();

  $event_class = new \ReflectionClass(Event::class);
  $event_constants = $event_class->getConstants();

  $total_events = count($event_constants);
  foreach ($event_constants as $event_name) {
    if ($event_name === $first_event) {
      continue;
    }
    $dispatcher->addListener($event_name, function ($e, $name) use (&$event_list, $total_events, &$script_control) {
      if (isset($event_list[$name])) {
        return;
      }

      if ($e instanceof Event\TestEvent && $e->getTest()->hasFailed()) {
        throw new \RuntimeException(sprintf('%s: The event list documentation failed to generate, probably because the test server is not running. Run %s and re-compile.', $e->getTest(), './bin/start_test_server.sh'));
      }

      $event_list[$name] = [
        'order' => count($event_list) + 1,
        'event' => $name,
        'class' => get_class($e),
      ];

      if ($script_control['last_event'] === $name && empty($script_control['file_saved'])) {

        // Merge in the failed counterparts to the passed
        foreach (array_keys($event_list) as $event_name) {
          if (preg_match('/(.*)passed(.*)/', $event_name, $matches)) {
            $failed_event_name = $matches[1] . 'failed' . $matches[2];
            $event_list[$failed_event_name] = ['event' => $failed_event_name] + $event_list[$event_name];
          }
        }

        // Now this is the last event, we will save the JSON file.
        uasort($event_list, function ($a, $b) {
          return $a['order'] - $b['order'];
        });
        $event_list = array_values($event_list);

        file_put_contents(__DIR__ . '/events.json', json_encode($event_list, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $script_control['file_saved'] = TRUE;
      }
    });
  }
});

add_directory(__DIR__ . '/../../example/tests');
add_directory(__DIR__);
load_config('config/local');
run_suite('suite');
