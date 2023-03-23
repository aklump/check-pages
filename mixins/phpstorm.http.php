<?php
/**
 * @file
 * Writes requests to a PhpStorm HTTP Client file
 *
 * @code
 *   add_mixin('phpstorm.http', [
 *     'single_file' => TRUE,
 *     'exclude_passing' => FALSE,
 *   ]);
 * @endcode
 *
 * https://www.jetbrains.com/help/phpstorm/http-client-in-product-code-editor.html
 */

namespace AKlump\CheckPages\Mixins;

use AKlump\CheckPages\Browser\RequestDriverInterface;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\SuiteEventInterface;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Files\FilesProviderInterface;
use AKlump\CheckPages\Files\HttpLogging;
use AKlump\CheckPages\Files\LocalFilesProvider;
use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\Parts\Test;

/** @var \AKlump\CheckPages\Parts\Runner $runner */
/** @var array $mixin_config */

$log_files = $runner->getLogFiles();
if (!$log_files) {
  throw new \RuntimeException('This mixin must come AFTER load_config(); edit your runner file and move this invocation.');
}

$mixin = new PhpStormHttpMixin($log_files, $mixin_config);

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

final class PhpStormHttpMixin {

  /**
   * @var string
   */
  private $logFiles;

  public function __construct(LocalFilesProvider $log_files, array $mixin_config) {
    $this->options = $mixin_config;
    $this->logFiles = $log_files;
  }

  public function getFilepath(Suite $suite): string {
    if ($this->options['single_file'] ?? FALSE) {
      $basename = parse_url($suite->getRunner()->get('base_url'), PHP_URL_HOST);
    }
    if (empty($basename)) {
      $basename = $suite->toFilepath();
    }
    $filepath = $this->logFiles->tryResolveFile("phpstorm/$basename.http", [], FilesProviderInterface::RESOLVE_NON_EXISTENT_PATHS)[0];
    $this->logFiles->tryCreateDir(dirname($filepath));

    return $filepath;
  }

  public function export(Test $test, RequestDriverInterface $driver): string {
    $config = $test->getConfig();

    return HttpLogging::request($config['why'] ?? '', $config['request']['method'] ?? 'get', $test->getAbsoluteUrl(), $driver->getHeaders(), $config['request']['body'] ?? '');
  }


}
