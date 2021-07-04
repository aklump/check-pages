<?php

/**
 * @file
 * This file bootstraps the app.
 */

use AKlump\CheckPages\CheckPages;
use AKlump\LoftLib\Bash\Bash;
use AKlump\LoftLib\Bash\Color;
use AKlump\LoftLib\Storage\FilePath;

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

$app = new CheckPages(ROOT);
$app->setRunner($cli->getArg(1), $cli->getParams());
$runner_dir = dirname(rtrim(getcwd(), '/') . '/' . $cli->getArg(1));
$app->addResolveDirectory($runner_dir)->setPathToSuites($runner_dir);

if (($dir = $cli->getParam('dir'))) {
  if (!is_dir($dir)) {
    throw new \InvalidArgumentException("\"$dir\" must be an existing directory.");
  }
  $app->addResolveDirectory($dir);
  $app->setPathToSuites($dir);
}

$test_runner = $app->getRunner();
if (!$test_runner) {
  throw new \InvalidArgumentException('You must pass a resolvable path to your PHP runner file, as the only argument, e.g., ./check_pages runner.php');
}

require_once ROOT . '/includes/runner_functions.inc';
