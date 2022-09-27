<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Exceptions\BadSyntaxException;
use AKlump\CheckPages\Parts\Runner;
use Symfony\Component\Yaml\Yaml;

final class Importer {

  /**
   * @var \AKlump\CheckPages\Parts\Runner
   */
  private $runner;

  public function __construct(Runner $runner) {
    $this->runner = $runner;
  }

  public function resolveImports(array &$test): void {
    foreach ($test as $key => $item) {
      if (!isset($item['import'])) {
        continue;
      }
      $import_code = $this->loadImport($item['import']);
      array_splice($test, $key, 1, $import_code);
    }
  }

  public function loadImport(string $import): array {
    try {
      $path_to_import_file = $this->runner->resolveFile($import);
    }
    catch (\Exception $exception) {
      $info = pathinfo($import);
      $underscored = $info['dirname'] . '/_' . $info['basename'];
      $path_to_import_file = $this->runner->resolveFile($underscored);
    }

    $loaded = Yaml::parseFile($path_to_import_file);
    if (!is_array($loaded) || !is_numeric(key($loaded))) {
      throw new BadSyntaxException(sprintf('Imports must parse to an indexed array; check "%s".', $import));
    }

    return $loaded;
  }

}
