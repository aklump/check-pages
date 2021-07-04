<?php

/**
 * @file
 * Compile the plugins into the main app.
 */

use AKlump\CheckPages\CheckPages;
use AKlump\CheckPages\PluginsCompiler;
use AKlump\CheckPages\PluginsManager;

define('ROOT', $argv[7]);

require_once ROOT . '/vendor/autoload.php';

$compiler = new PluginsCompiler(
  new PluginsManager(new CheckPages(ROOT), ROOT . '/plugins'),
  ROOT . '/schema.visit.json',
  ROOT . '/' . CheckPages::SCHEMA_VISIT . '.json',
  ROOT . '/example'
);
$compiler->compile();
