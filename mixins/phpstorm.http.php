<?php
/**
 * @file
 * Writes requests to a PhpStorm HTTP Client file
 *
 * @code
 *   add_mixin('phpstorm.http', [
 *     'single_file' => TRUE,
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
use AKlump\CheckPages\Files\LocalFilesProvider;
use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\Parts\Test;

/** @var \AKlump\CheckPages\Parts\Runner $runner */
/** @var array $mixin_config */

$mixin = new PhpStormHttpMixin($runner->getLogFiles(), $mixin_config);

respond_to(Event::SUITE_LOADED, function (SuiteEventInterface $event) use ($mixin) {
  fclose(fopen($mixin->getFilepath($event->getSuite()), 'w'));
});

respond_to(Event::REQUEST_READY, function (TestEventInterface $event) use ($mixin, $mixin_config) {
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
    $url = $test->getAbsoluteUrl();
    if (!$url) {
      return '';
    }
    $config = $test->getConfig();

    $export = [];
    $export[] = '### ' . ($config['why'] ?? '');

    // @link https://www.jetbrains.com/help/phpstorm/exploring-http-syntax.html#enable-disable-saving-cookies
    $export[] = '// @no-cookie-jar';

    $export[] = sprintf('%s %s', strtoupper($config['request']['method'] ?? 'GET'), $url);

    foreach ($driver->getHeaders() as $key => $value) {
      $export[] = sprintf('%s: %s', $key, $value);
    }

    if (!empty($config['request']['body'])) {
      $content_type = $driver->getHeaders()['content-type'] ?? 'application/octet-stream';
      $export[] = PHP_EOL . $this->getStringBody($content_type, $config['request']['body']);
    }

    return implode(PHP_EOL, $export) . PHP_EOL . PHP_EOL;
  }

  private function getStringBody($content_type, $body) {
    if (!is_string($body)) {
      switch ($content_type) {
        case 'application/json':
          $body = json_encode($body);
          break;

        default:
          $body = http_build_query($body);
          break;
      }
    }

    return strval($body);
  }
}
