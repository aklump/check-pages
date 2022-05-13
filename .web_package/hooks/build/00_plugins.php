<?php

/**
 * @file
 * Compile the plugins into the main app.
 */

use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Plugin\PluginsCompiler;
use AKlump\CheckPages\Plugin\PluginsManager;
use Symfony\Component\Console\Input\ArrayInput;

define('ROOT', $argv[7]);

require_once ROOT . '/vendor/autoload.php';

$compiler = new PluginsCompiler(
  new PluginsManager(new Runner(ROOT, new ArrayInput([]), new Symfony\Component\Console\Output\NullOutput()), ROOT . '/plugins'),
  ROOT . '/schema.suite.json',
  ROOT . '/' . Runner::SCHEMA_VISIT . '.json',
  ROOT . '/services.yml',
  ROOT . '/services.DO_NOT_EDIT.yml',
  ROOT . '/composer.json',
  ROOT . '/example'
);
$compiler->compile();

echo "You may need to run `composer dump` if autoloading has changed." . PHP_EOL;
