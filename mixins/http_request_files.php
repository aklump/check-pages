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
use AKlump\CheckPages\Event\SuiteEventInterface;
use AKlump\CheckPages\Event\TestEventInterface;

/** @var \AKlump\CheckPages\Parts\Runner $runner */
/** @var array $mixin_config */

$log_files = $runner->getLogFiles();
if (!$log_files) {
  throw new \RuntimeException('This mixin must come AFTER load_config(); edit your runner file and move this invocation.');
}

$mixin = new HttpRequestFiles($log_files, $mixin_config);

respond_to(Event::SUITE_STARTED, function (SuiteEventInterface $event) use ($mixin) {
  fclose(fopen($mixin->getFilepath($event->getSuite()), 'w'));
});

respond_to(Event::REQUEST_PREPARED, function (TestEventInterface $event) use ($mixin, $mixin_config) {
  $http = fopen($mixin->getFilepath($event->getTest()->getSuite()), 'a');
  try {
    $export = $mixin->export(
      $event->getTest(),
      $event->getDriver()
    );
    if (!empty($export)) {
      fwrite($http, $export);
    }
    fclose($http);
  }
  catch (\Exception $exception) {
    fclose($http);
  }
});

respond_to(Event::SUITE_FINISHED, function (Event\SuiteEventInterface $event) use ($mixin) {
  $path = $mixin->getFilepath($event->getSuite());
  if (file_exists($path) && filesize($path) === 0) {
    unlink($path);
  }
});
