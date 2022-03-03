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
use Symfony\Component\EventDispatcher\EventDispatcher;


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

// Enter valid flags.
$valid_flags = ['v', 'h'];
if (($diff = array_diff($cli->getFlags(), $valid_flags))) {
  throw new \InvalidArgumentException("Unknown flag: " . reset($diff));
}

// Enter valid parameters.
$valid_parameters = [
  'help',
  'dir',
  'config',
  'quiet',
  'show-request-headers',
  'show-request',
  'show-headers',
  'show-response',

  // A single suite filename or CSV string of them.
  'filter',
];
if (($diff = array_diff_key($cli->getParams(), array_flip($valid_parameters)))) {
  throw new \InvalidArgumentException("Unknown parameter: " . key($diff));
}

$print_version = function () {
  $data = FilePath::create(ROOT . '/composer.json')->load()->getJson();
  $credits = sprintf("Check Pages by In the Loft Studios ~ Version %s", $data->version);
  echo Color::wrap('cyan', $credits) . PHP_EOL . PHP_EOL;
};

// Display the version.
if ($cli->hasFlag('v')) {
  $print_version();
  exit(0);
}

if ($cli->hasFlag('h') || $cli->hasParam('help')) {
  $print_version();

  // TODO Build this out with hooks for plugins or something more dynamic.

  echo "Parameters:" . PHP_EOL;
  sort($valid_parameters);
  echo implode(PHP_EOL, array_map(function ($p) {
      return "  --$p";
    }, $valid_parameters)) . PHP_EOL . PHP_EOL;

  echo "Flags:" . PHP_EOL;
  sort($valid_flags);
  echo implode(PHP_EOL, array_map(function ($flag) {
      return "  -$flag";
    }, $valid_flags)) . PHP_EOL . PHP_EOL;

  exit(0);
}

$runner = new Runner(ROOT);
$path_to_runner = $cli->getArg(1, '');
$runner->setBasename(basename($path_to_runner), $cli->getParams());

// Add the directory of the runner as a resolvable directory and path to suites.
if ($path_to_runner) {
  $realpath_to_runner = realpath($path_to_runner);
  if (!$realpath_to_runner) {
    throw new \InvalidArgumentException("The runner file: \"$path_to_runner\" does not exist.");
  }
  $runner_dir = dirname($realpath_to_runner);
  $runner->addResolveDirectory($runner_dir)->setPathToSuites($runner_dir);
}

if (($dir = $cli->getParam('dir'))) {
  $dir = realpath($dir);
  if (!is_dir($dir)) {
    throw new \InvalidArgumentException("\"$dir\" must be an existing directory.");
  }

  // This path to suites needs to overwrite that from runner directory above.
  $runner->addResolveDirectory($dir)->setPathToSuites($dir);
}

if (!$runner->getRunnerPath()) {
  throw new \InvalidArgumentException('You must pass a resolvable path to your PHP runner file, as the only argument, e.g., ./check_pages runner.php');
}

require_once ROOT . '/includes/runner_functions.inc';
