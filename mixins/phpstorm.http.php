<?php
/**
 * @file
 * Writes requests to a PhpStorm HTTP Client file
 *
 * @code
 *   add_mixin('phpstorm.http', [
 *     'output' => config_get('files/phpstorm'),
 *     'single_file' => TRUE,
 *     'exclude_passing' => TRUE,
 *   ]);
 * @endcode
 *
 * https://www.jetbrains.com/help/phpstorm/http-client-in-product-code-editor.html
 */

namespace AKlump\CheckPages\Mixins;

use AKlump\CheckPages\Event\OnAfterAssert;
use AKlump\CheckPages\Event\OnLoadSuite;
use AKlump\CheckPages\Event\SuiteEventInterface;
use AKlump\CheckPages\Exceptions\StopRunnerException;
use AKlump\CheckPages\Exceptions\UnresolvablePathException;
use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\RequestDriverInterface;


//
// Verify the output directory.
//
if (empty($config['output'])) {
  throw new StopRunnerException('Missing config value "output".  Please add the directory where .http files will be saved.');
}

try {
  $output_dir = $runner->resolve($config['output']);
  $mixin = new PhpStormHttpMixin($output_dir, $config);
}
catch (UnresolvablePathException $exception) {
  throw new StopRunnerException(sprintf('The output directory "%s" cannot be resolved; please create it manually.', $config['output']));
}

if (!is_writable($output_dir)) {
  throw new StopRunnerException(sprintf('The output directory "%s" is not writeable.  Please update it\'s permissions.', $output_dir));
}

respond_to_event(OnLoadSuite::class, function (SuiteEventInterface $event) use ($mixin) {
  $http = fopen($mixin->getFilepath($event->getSuite()), 'w');
  fclose($http);
});

respond_to_event(OnAfterAssert::class, function (OnAfterAssert $event) use ($mixin, $config) {
  $did_pass = $event->getAssert()->getResult();
  if ($did_pass && TRUE === $config['exclude_passing']) {
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

final class PhpStormHttpMixin {

  /**
   * @var string
   */
  private $outputDir;

  public function __construct(string $output_dir, array $config) {
    $this->options = $config;
    $this->outputDir = $output_dir;
  }

  public function getFilepath(Suite $suite): string {
    $basename = $suite->id();
    if ($this->options['single_file'] ?? FALSE) {
      $basename = parse_url($suite->getConfig()['base_url'], PHP_URL_HOST);
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
    $export[] = sprintf('%s %s', strtoupper($config['request']['method'] ?? 'GET'), $url);

    foreach ($driver->getHeaders() as $key => $value) {
      $export[] = sprintf('%s: %s', $key, $value);
    }

    if (!empty($config['request']['body'])) {
      $export[] = PHP_EOL . $config['request']['body'];
    }

    return implode(PHP_EOL, $export) . PHP_EOL . PHP_EOL;
  }
}
