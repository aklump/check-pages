<?php

/**
 * @file
 * This file bootstraps the app.
 */

use AKlump\CheckPages\CheckPages;
use AKlump\CheckPages\Parts\Runner;
use AKlump\LoftLib\Bash\Bash;
use AKlump\LoftLib\Bash\Color;
use AKlump\LoftLib\Storage\FilePath;


if (!class_exists("AKlump\CheckPages\Parts\Runner")) {
  $root = realpath(__DIR__ . '/../');
  throw new \RuntimeException(sprintf('Testing unavailable due to missing dependencies; do this then try again: "cd %s && composer install"', $root));
}

// This is a hack because Bash doesn't allow commas in param values.
$argv = array_map(function ($line) {
  return preg_replace_callback('/\-\-filter=(.+)/', function ($matches) {
    return '--filter=' . str_replace(',', ' ', $matches[1]);
  }, $line);
}, $argv);

$cli = new Bash($argv);

// Validate flags.
if (($diff = array_diff($cli->getFlags(), ['v']))) {
  throw new \InvalidArgumentException("Unknown flag: " . reset($diff));
}

// Validate parameters.
if (($diff = array_diff_key($cli->getParams(), array_flip([

  // Enter valid parameters.
  'dir',
  'config',
  'quiet',
  'show-source',
  'show-request',
  'show-response',

  // A single suite filename or CSV string of them.
  'filter',
])))) {
  throw new \InvalidArgumentException("Unknown parameter: " . key($diff));
}

// Display the version.
if ($cli->hasFlag('v')) {
  $data = FilePath::create(ROOT . '/composer.json')->load()->getJson();
  $credits = sprintf("Check Pages by In the Loft Studios ~ Version %s", $data->version);
  echo Color::wrap('cyan', $credits) . PHP_EOL . PHP_EOL;
  exit(0);
}

$app = new Runner(ROOT);
$path_to_runner = $cli->getArg(1, '');
$app->setRunner(basename($path_to_runner), $cli->getParams());

// Add the directory of the runner as a resolvable directory and path to suites.
if ($path_to_runner) {
  $realpath_to_runner = realpath($path_to_runner);
  if (!$realpath_to_runner) {
    throw new \InvalidArgumentException("The runner file: \"$path_to_runner\" does not exist.");
  }
  $runner_dir = dirname($realpath_to_runner);
  $app->addResolveDirectory($runner_dir)->setPathToSuites($runner_dir);
}

if (($dir = $cli->getParam('dir'))) {
  $dir = realpath($dir);
  if (!is_dir($dir)) {
    throw new \InvalidArgumentException("\"$dir\" must be an existing directory.");
  }

  // This path to suites needs to overwrite that from runner directory above.
  $app->addResolveDirectory($dir)->setPathToSuites($dir);
}

$test_runner = $app->getRunner();
if (!$test_runner) {
  throw new \InvalidArgumentException('You must pass a resolvable path to your PHP runner file, as the only argument, e.g., ./check_pages runner.php');
}

require_once ROOT . '/includes/runner_functions.inc';
