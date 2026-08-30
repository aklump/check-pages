#!/usr/bin/env php
<?php

use AKlump\FixtureFramework\Discovery\DiscoverFixtureDefinitions;
use AKlump\FixtureFramework\Runtime\FixtureCollectionBuilder;
use AKlump\FixtureFramework\Runtime\FixtureInstantiator;
use AKlump\FixtureFramework\Runtime\FixtureRunner;
use AKlump\FixtureFramework\Runtime\RunOptions;

$vendor_dir = __DIR__ . '/../vendor';
require_once $vendor_dir . '/autoload.php';

$flush = in_array('--flush', $argv);
$silent = in_array('--silent', $argv);
$filter = '';
foreach ($argv as $arg) {
  if (str_starts_with($arg, '--filter=')) {
    $filter = substr($arg, 9);
    break;
  }
}

try {
  $definitions = (new DiscoverFixtureDefinitions())($vendor_dir, [
    'AuroraTime\Tests\Fixture',
  ], $flush, $silent, $filter);
}
catch (Exception $e) {
  echo "Error ordering fixtures: " . $e->getMessage() . "\n";
  exit(1);
}

try {
  $checkpages = shell_exec('which checkpages');
  $checkpages = $checkpages ? trim($checkpages) : '';
  $run_options = new RunOptions([
    'env' => 'test',
    'url' => 'https://auroratime.local.loft/',
    'drush' => 'lando nxdb_drush',
    'checkpages' => $checkpages,
  ]);
  $instantiator = new FixtureInstantiator($run_options);
  $fixtures = (new FixtureCollectionBuilder($instantiator))($definitions);
  $runner = new FixtureRunner($fixtures);
  $runner->run($silent);
}
catch (\Exception $e) {
  print $e->getMessage() . "\n";
  exit(1);
}
