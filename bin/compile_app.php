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

const ROOT = __DIR__ . '/../';

require_once ROOT . '/vendor/autoload.php';
$compiler = new AppCompiler(
  new HandlersManager(realpath(ROOT . '/includes/' . CheckPages::DIR_HANDLERS)),
  ROOT . '/schema.suite.json',
  ROOT . '/' . Runner::SCHEMA_VISIT . '.json',
  ROOT . '/services.yml',
  ROOT . '/services.DO_NOT_EDIT.yml',
  ROOT . '/composer.core.json',
  ROOT . '/composer.json',
  ROOT . '/example'
);
$compiler->compile();

system('composer dump; composer update');
