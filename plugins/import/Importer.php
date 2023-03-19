<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Exceptions\BadSyntaxException;
use AKlump\CheckPages\Files\FilesProviderInterface;
use Symfony\Component\Yaml\Yaml;

final class Importer {

  /**
   * @var \AKlump\CheckPages\Files\FilesProviderInterface
   */
  private $files;

  public function __construct(FilesProviderInterface $files) {
    $this->files = $files;
  }

  public function resolveImports(array &$test): void {
    $resolved_test = [];
    foreach ($test as $key => $item) {
      if (!isset($item['import'])) {
        $resolved_test[$key] = $item;
        continue;
      }
      $import_code = $this->loadImport($item['import']);
      $resolved_test = array_merge($resolved_test, $import_code);
    }
    $test = $resolved_test;
  }

  public function loadImport(string $import): array {
    try {
      $path_to_import_file = $this->files->tryResolveFile($import, [
        'yaml',
        'yml',
      ])[0];
    }
    catch (\Exception $exception) {
      $info = pathinfo($import);
      $underscored = $info['dirname'] . '/_' . $info['basename'];
      $path_to_import_file = $this->files->tryResolveFile($underscored, [
        'yaml',
        'yml',
      ])[0];
    }

    $loaded = Yaml::parseFile($path_to_import_file);
    if (!is_array($loaded) || !is_numeric(key($loaded))) {
      throw new BadSyntaxException(sprintf('Imports must parse to an indexed array; check "%s".', $import));
    }

    return $loaded;
  }

}
