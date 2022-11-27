<?php
/**
 * @file
 * Writes requests to a PhpStorm HTTP Client file
 *
 * @code
 *   add_mixin('phpstorm.http', [
 *     'output' => config_get('files') . '/phpstorm',
 *     'single_file' => TRUE,
 *     'exclude_passing' => TRUE,
 *   ]);
 * @endcode
 *
 * https://www.jetbrains.com/help/phpstorm/http-client-in-product-code-editor.html
 */

namespace AKlump\CheckPages\Mixins;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\SuiteEventInterface;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Exceptions\StopRunnerException;
use AKlump\CheckPages\Exceptions\UnresolvablePathException;
use AKlump\CheckPages\Files;
use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\RequestDriverInterface;

//
// Verify the output directory.
//
if (empty($mixin_config['output'])) {
  throw new StopRunnerException('Missing config value "output".  Please add the directory where .http files will be saved.');
}

try {
  $files = new Files($runner);
  $output_dir = $files->prepareDirectory($mixin_config['output']);
  $mixin = new PhpStormHttpMixin($output_dir, $mixin_config);
}
catch (UnresolvablePathException $exception) {
  throw new StopRunnerException(sprintf('The output directory "%s" cannot be resolved; please ensure it exists and try again.', $mixin_config['output']));
}

respond_to(Event::SUITE_LOADED, function (SuiteEventInterface $event) use ($mixin) {
  fclose(fopen($mixin->getFilepath($event->getSuite()), 'w'));
});

respond_to(Event::REQUEST_TEST_FINISHED, function (TestEventInterface $event) use ($mixin, $mixin_config) {
  $did_pass = !$event->getTest()->hasFailed();
  if ($did_pass && TRUE === ($mixin_config['exclude_passing'] ?? FALSE)) {
    return;
  }

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
  private $outputDir;

  public function __construct(string $output_dir, array $mixin_config) {
    $this->options = $mixin_config;
    $this->outputDir = $output_dir;
  }

  public function getFilepath(Suite $suite): string {
    $basename = $suite->id();
    if ($this->options['single_file'] ?? FALSE) {
      $basename = parse_url($suite->getRunner()->getConfig()['base_url'], PHP_URL_HOST);
    }
    $basename = preg_replace('/[\.\- ]/', '_', $basename);

    return sprintf('%s/%s.http', rtrim($this->outputDir, '/'), $basename);
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
