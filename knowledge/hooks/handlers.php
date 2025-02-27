<?php

/**
 * @file
 * Add any plugin/README.md file to the documentation as a single page.
 */

/** @var \AKlump\LoftDocs\Compiler $compiler */

use AKlump\CheckPages\CheckPages;
use AKlump\LoftLib\Code\Strings;

require_once __DIR__ . '/../../vendor/autoload.php';

$path_to_handlers = __DIR__ . '/../../includes/' . CheckPages::DIR_HANDLERS;
$handlers = scandir($path_to_handlers);


foreach ($handlers as $handler) {
  $contents = NULL;
  if ($handler === '.' || $handler === '..') {
    continue;
  }

  $path_to_examples = get_path_to_examples($handler);
  $examples = NULL;
  if ($path_to_examples) {
    $examples = "## Example Tests\n\n";
    $examples .= sprintf("```yaml\n%s\n```", file_get_contents($path_to_examples));
  }

  $frontmatter = get_frontmatter($handler);

  // Add a page for the plugin's README file.
  $readme = "$path_to_handlers/$handler/README.md";
  if (is_file($readme)) {
    $readme = file_get_contents($readme);
    $contents = <<<EOD
{$frontmatter}

$readme

$examples
EOD;

  }
  elseif ($suite) {
    $title = Strings::title($handler);
    $contents = <<<EOD
{$frontmatter}

# $title

## Example Tests

$suite 
EOD;
  }

  if (!empty($contents)) {
    $contents .= PHP_EOL . PHP_EOL . "_Provided by the {$handler} handler._";
    $compiler->addSourceFile("demo--$handler.md", $contents);
  }
}
