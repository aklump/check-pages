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
  'debug',
  'show-source',
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

$app = new CheckPages(ROOT, $cli);
if (($dir = $cli->getParam('dir'))) {
  if (!is_dir($dir)) {
    throw new \InvalidArgumentException("\"$dir\" must be an existing directory.");
  }
  $app->addResolveDirectory($dir);
}

$test = $app->getRunner();
if (!$test) {
  throw new \InvalidArgumentException('You must pass a resolvable path to your PHP runner file, as the only argument, e.g., ./check_pages runner.php');
}

require_once ROOT . '/includes/runner_functions.inc';
