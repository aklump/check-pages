<?php
/**
 * @file
 * Locate all the callback methods to be used by add_test_option().
 */

use AKlump\CheckPages\Assert;
use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\Parts\Suite;
use Psr\Http\Message\ResponseInterface;

require_once __DIR__ . '/../../vendor/autoload.php';

$path_to_options = __DIR__ . '/../../plugins/options/Options.php';
$source = file_get_contents($path_to_options);
$chunks = preg_split('/protected|public|private function/', $source);
$chunks = array_filter($chunks, function ($chunk) {
  return strstr($chunk, '$this->handleCallbackByHook(__FUNCTION');
});
$callbacks = array_map(function ($chunk) {
  preg_match('/function\s*([^ \(]+)\s*\((.+)\)/', $chunk, $matches);
  $method_name = $matches[1];
  switch ($method_name) {
    case 'onLoadSuite':
      return sprintf('* `%s(%s, array $context)`', $matches[1], $matches[2]) . PHP_EOL;

    default:
      return sprintf('* `%s($option, %s, array $context)`', $matches[1], $matches[2]) . PHP_EOL;
  }

}, $chunks);

$callbacks = str_replace('Suite $', '\\' . Suite::class . ' $', $callbacks);
$callbacks = str_replace('Test $', '\\' . Test::class . ' $', $callbacks);
$callbacks = str_replace('Assert $', '\\' . Assert::class . ' $', $callbacks);
$callbacks = str_replace('ResponseInterface $', '\\' . ResponseInterface::class . ' $', $callbacks);

echo $compiler->addInclude('_callbacks.md', $callbacks)
    ->getBasename() . ' has been created.' && exit(0);
exit(1);
