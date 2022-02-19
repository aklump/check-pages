<?php
/**
 * @file
 * Locate all the callback methods to be used by add_test_option().
 */

require_once __DIR__ . '/../../vendor/autoload.php';

$path_to_options = __DIR__ . '/../../plugins/options/Options.php';
$source = file_get_contents($path_to_options);
$chunks = preg_split('/protected|public|private function/', $source);
$chunks = array_filter($chunks, function ($chunk) {
  return strstr($chunk, '$this->handleCallbackByHook(__FUNCTION');
});
$callbacks = array_map(function ($chunk) {
  preg_match('/function\s*([^ \(]+)\s*\((.+)\)/', $chunk, $matches);

  return sprintf('* `%s($option, %s, array $context)`', $matches[1], $matches[2]) . PHP_EOL;
}, $chunks);

echo $compiler->addInclude('_callbacks.md', $callbacks)
  ->getBasename() . ' has been created.' && exit(0);
exit(1);
