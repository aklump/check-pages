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
    $importer = new Importer($this->runner);
    foreach ($this->suite->getTests() as $test) {
      $config = $test->getConfig();

      // Handle TEST imports.
      if (!empty($config['import'])) {
        $insert_code = $importer->loadImport($config['import']);
        $this->suite->replaceTestWithMultiple($test, $insert_code);
      }

      elseif (!empty($config['find']) && is_array($config['find'])) {
        $importer->resolveImports($config['find']);
        $test->setConfig($config);
      }
    }
  }

}
