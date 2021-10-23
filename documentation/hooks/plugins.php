<?php

/**
 * @file
 * Add any plugin/README.md file to the documentation as a single page.
 */

/**
 * @var \AKlump\LoftDocs\Compiler
 */
global $compiler;

$path_to_plugins = realpath(__DIR__ . '/../../plugins');
$plugins = scandir($path_to_plugins);
foreach ($plugins as $plugin) {
  $readme = "$path_to_plugins/$plugin/README.md";
  if (0 === strpos($plugin, '.') || !is_file($readme)) {
    continue;
  }
  $compiler->addSourceFile("plugin_$plugin.md", file_get_contents($readme));
  echo "$plugin/README.md has been added";
}
