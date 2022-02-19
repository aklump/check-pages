<?php

/**
 * @file
 * Add any plugin/README.md file to the documentation as a single page.
 */

use AKlump\LoftLib\Code\Strings;

$path_to_plugins = realpath(__DIR__ . '/../../plugins');
$plugins = scandir($path_to_plugins);

foreach ($plugins as $plugin) {
  $contents = NULL;
  if ($plugin === '.' || $plugin === '..') {
    continue;
  }

  $path_to_suite = "$path_to_plugins/$plugin/suite.yml";
  $suite = NULL;
  if (is_file($path_to_suite)) {
    $suite = sprintf("```yaml\n%s\n```", file_get_contents($path_to_suite));
  }

  // Add a page for the plugin's README file.
  $readme = "$path_to_plugins/$plugin/README.md";
  if (is_file($readme)) {
    $readme = file_get_contents($readme);
    $contents = <<<EOD
<!--
id: plugin_{$plugin}
-->

$readme

## Example Tests

$suite
EOD;

  }
  elseif ($suite) {
    $title = Strings::title($plugin);
    $contents = <<<EOD
<!--
id: plugin_{$plugin}
-->

# $title

## Example Tests

$suite 
EOD;
  }

  if (!empty($contents)) {
    $contents .= PHP_EOL . PHP_EOL . "_Provided by the {$plugin} plugin._";
    $compiler->addSourceFile("demo--$plugin.md", $contents);
  }
}
