<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Event\SuiteEventInterface;
use AKlump\CheckPages\Exceptions\BadSyntaxException;
use Symfony\Component\Yaml\Yaml;

/**
 * Implements the Imports plugin.
 */
final class Import extends LegacyPlugin {

  /**
   * @var \AKlump\CheckPages\Parts\Suite
   */
  private $suite;

  public function onLoadSuite(SuiteEventInterface $event) {
    $this->suite = $event->getSuite();
    foreach ($this->suite->getTests() as $test) {
      $config = $test->getConfig();

      // Handle TEST imports.
      if (!empty($config['import'])) {
        $insert_code = $this->loadImport($config['import']);
        $this->suite->replaceTestWithMultiple($test, $insert_code);
      }

      elseif (!empty($config['find']) && is_array($config['find'])) {
        $mutated = [];
        foreach ($config['find'] as $assertion) {

          // Handle ASSERTION imports.
          if (empty($assertion['import'])) {
            $mutated[] = $assertion;
          }
          else {
            $insert_code = $this->loadImport($assertion['import']);
            foreach ($insert_code as $item) {
              $mutated[] = $item;
            }
          }
        }
        $config['find'] = $mutated;
        $test->setConfig($config);
      }
    }
  }

  private function loadImport(string $import): array {
    try {
      $path_to_import_file = $this->suite->getRunner()->resolveFile($import);
    }
    catch (\Exception $exception) {
      $info = pathinfo($import);
      $underscored = $info['dirname'] . '/_' . $info['basename'];
      $path_to_import_file = $this->suite->getRunner()
        ->resolveFile($underscored);
    }

    $loaded = Yaml::parseFile($path_to_import_file);
    if (!is_array($loaded) || !is_numeric(key($loaded))) {
      throw new BadSyntaxException(sprintf('Imports must parse to an indexed array; check "%s".', $import));
    }

    return $loaded;
  }

}
