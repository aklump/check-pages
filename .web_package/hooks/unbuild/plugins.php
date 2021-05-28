<?php

use AKlump\CheckPages\CheckPages;

define('ROOT', $argv[7]);

require_once ROOT . '/vendor/autoload.php';

file_exists(ROOT . '/' . CheckPages::SCHEMA_VISIT . '.json') && unlink(ROOT . '/' . CheckPages::SCHEMA_VISIT . '.json');
