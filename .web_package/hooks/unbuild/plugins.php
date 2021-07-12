<?php

use AKlump\CheckPages\Parts\Runner;

define('ROOT', $argv[7]);

require_once ROOT . '/vendor/autoload.php';

file_exists(ROOT . '/' . Runner::SCHEMA_VISIT . '.json') && unlink(ROOT . '/' . Runner::SCHEMA_VISIT . '.json');
