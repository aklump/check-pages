<?php

/**
 * @file
 * Compile the plugins into the main app.
 */

use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Plugin\PluginsCompiler;
use AKlump\CheckPages\Plugin\PluginsManager;

define('ROOT', $argv[7]);

require_once ROOT . '/vendor/autoload.php';

$compiler = new PluginsCompiler(
  new PluginsManager(new Runner(ROOT, new \Symfony\Component\Console\Input\ArrayInput([]), new Symfony\Component\Console\Output\NullOutput()), ROOT . '/plugins'),
  ROOT . '/schema.test.json',
  ROOT . '/' . Runner::SCHEMA_VISIT . '.json',
  ROOT . '/example'
);
$compiler->compile();
