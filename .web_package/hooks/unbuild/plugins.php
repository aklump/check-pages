<?php

use AKlump\CheckPages\Parts\Runner;

define('ROOT', $argv[7]);

require_once ROOT . '/vendor/autoload.php';

file_exists(ROOT . '/' . Runner::PATH_TO_SCHEMA__SUITE) && unlink(ROOT . '/' . Runner::PATH_TO_SCHEMA__SUITE);
