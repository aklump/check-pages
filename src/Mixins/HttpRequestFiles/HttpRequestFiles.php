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

  private array $options;

  public function __construct(array $mixin_config, LocalFilesProvider $log_files) {
    $this->options = $mixin_config;
    $this->logFiles = $log_files;
  }

  private function getFilepath(Suite $suite): string {
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

  /**
   * Get the log entry contents for a request.
   *
   * @param \AKlump\CheckPages\Parts\Test $test
   * @param \AKlump\CheckPages\Browser\RequestDriverInterface $driver
   *
   * @return string
   */
  public function getRequestLogEntry(Test $test, RequestDriverInterface $driver): string {
    $why = $test->getConfig()['why'] ?? '';

    return HttpLogging::request($why, $driver->getMethod(), (string) $driver->getUri(), $driver->getHeaders(), trim($driver->getBody()));
  }

  /**
   * Save the request log entry to file.
   *
   * @param string $file_contents
   * @param \AKlump\CheckPages\Parts\Suite $suite
   *
   * @return void
   */
  public function saveRequestToLogFile(string $file_contents, Suite $suite) {
    // The first time we try to write a request for a given suite we're going to
    // empty truncate the file.  After that we will append.
    static $suites_started = [];
    $mode = 'a';
    if (empty($suites_started[$suite->id()])) {
      $suites_started[$suite->id()] = TRUE;
      $mode = 'w';
    }
    if (!empty($file_contents)) {
      $http = fopen($this->getFilepath($suite), $mode);
      fwrite($http, $file_contents);
      fclose($http);
    }
  }

}
