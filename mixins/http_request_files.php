<?php
/**
 * @file
 * Writes requests to a PhpStorm HTTP Client file
 *
 * @code
 *   add_mixin('http_request_files', [
 *     'single_file' => TRUE,
 *     'exclude_passing' => FALSE,
 *   ]);
 * @endcode
 *
 * https://www.jetbrains.com/help/phpstorm/http-client-in-product-code-editor.html
 */

namespace AKlump\CheckPages\Mixins\HttpRequestFiles;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\DriverEventInterface;

/** @var \AKlump\CheckPages\Parts\Runner $runner */
/** @var array $mixin_config */

$log_files = $runner->getLogFiles();
if (!$log_files) {
  throw new \RuntimeException('This mixin must come AFTER load_config(); edit your runner file and move this invocation.');
}

$mixin = new HttpRequestFiles($mixin_config, $log_files);
respond_to(Event::REQUEST_PREPARED, function (DriverEventInterface $event) use ($mixin) {
  $file_contents = $mixin->getRequestLogEntry($event->getTest(), $event->getDriver());
  $mixin->saveRequestToLogFile($file_contents, $event->getTest()->getSuite());
});
