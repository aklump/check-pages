<?php

/**
 * @file
 * Compile the plugins into the main app.
 */

use AKlump\CheckPages\PluginsManager;
use AKlump\CheckPages\PluginsCompiler;

define('ROOT', $argv[7]);

require_once ROOT . '/vendor/autoload.php';

$compiler = new PluginsCompiler(
  new PluginsManager(ROOT . '/plugins'),
  ROOT . '/schema.visit.json',
  ROOT . '/example'
);
$compiler->compile();
