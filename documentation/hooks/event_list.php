<?php

/**
 * @file
 *
 * Compile the event data (from JSON file) into markdown include.
 */

/** @var \AKlump\LoftDocs\Compiler $compiler */

use AKlump\CheckPages\Event;
use AKlump\LoftLib\Code\Markdown;

require_once __DIR__ . '/../../vendor/autoload.php';

$data_file = __DIR__ . '/../event_hook/events.json';
if (!file_exists($data_file)) {
  throw new \RuntimeException(sprintf('Missing data file: %s', $data_file));
}

$event_class = new ReflectionClass(Event::class);

// @see event_list_runner.php
$event_data = json_decode(file_get_contents($data_file), TRUE);

$event_data = array_combine(array_map(function (array $datum) {
  return $datum['event'];
}, $event_data), $event_data);

foreach ($event_class->getConstants() as $constant => $value) {
  $event_data[$value]['constant'] = $event_class->getShortName() . '::' . $constant;
}

$table_data = array_values(array_map(function (array $datum) {
  $shortname = explode('\\', $datum['class'] ?? '');
  $shortname = array_pop($shortname);

  return [
    'Event order' => $datum['order'] ?? '',
    'Event' => $datum['constant'],
    'Event class' => $shortname,
  ];
}, $event_data));

$heading = 'Events';
$table = Markdown::table($table_data, array_keys($table_data[0]));
$file_contents = $table . PHP_EOL;

echo $compiler->addInclude('_event_list.md', $file_contents)
    ->getBasename() . ' has been created.' && unlink($data_file) && exit(0);
exit(1);
