#!/usr/bin/env php
<?php

/**
 * @file
 * Compile the handlers into the main app.
 */

use AKlump\CheckPages\CheckPages;
use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Plugin\HandlersManager;
use AKlump\CheckPages\Service\AppCompiler;
use AKlump\LoftLib\Bash\Color;

const ROOT = __DIR__ . '/../';

require_once ROOT . '/vendor/autoload.php';
$compiler = new AppCompiler(
  new HandlersManager(realpath(ROOT . '/includes/' . CheckPages::DIR_HANDLERS)),
  ROOT . '/schema.suite.json',
  ROOT . '/' . Runner::PATH_TO_SCHEMA__SUITE,
  ROOT . '/services.yml',
  ROOT . Runner::PATH_TO_SERVICE_DEFINITIONS,
  ROOT . '/example'
);
try {
  $compiler->compile();
  system('composer dump');
  system('composer update');
}
catch (Exception $exception) {
  echo Color::wrap('white on red', $exception->getMessage()) . PHP_EOL;
  exit(1);
}
echo Color::wrap('white on green', 'Compile finished.') . PHP_EOL;
exit(0);


