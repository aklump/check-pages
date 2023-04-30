<?php

namespace AKlump\CheckPages\Mixins\HttpRequestFiles;

use AKlump\CheckPages\Browser\RequestDriverInterface;
use AKlump\CheckPages\Files\FilesProviderInterface;
use AKlump\CheckPages\Files\HttpLogging;
use AKlump\CheckPages\Files\LocalFilesProvider;
use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\Parts\Test;

class HttpRequestFiles {

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
    $filepath = $this->logFiles->tryResolveFile("http_request_files/$basename.http", [], FilesProviderInterface::RESOLVE_NON_EXISTENT_PATHS)[0];
    $this->logFiles->tryCreateDir(dirname($filepath));

    return $filepath;
  }

  public function export(Test $test, RequestDriverInterface $driver): string {
    $why = $test->getConfig()['why'] ?? '';

    return HttpLogging::request($why, $driver->getMethod(), (string) $driver->getUri(), $driver->getHeaders(), trim($driver->getBody()));
  }

}
