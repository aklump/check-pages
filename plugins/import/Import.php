<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Event\SuiteEventInterface;
use AKlump\CheckPages\Assert;
use AKlump\CheckPages\SerializationTrait;
use Symfony\Component\Yaml\Yaml;

/**
 * Implements the Imports plugin.
 */
final class Import extends LegacyPlugin {

  public function onLoadSuite(SuiteEventInterface $event) {
    $suite = $event->getSuite();
    $new_data = $suite->jsonSerialize();
    $needs_update = FALSE;
    foreach ($suite->getTests() as $index => $test) {
      $config = $test->getConfig();
      if (empty($config['import'])) {
        continue;
      }
      try {
        $import = $suite->getRunner()->resolveFile($config['import']);
      }
      catch (\Exception $exception) {
        $info = pathinfo($config['import']);
        $underscored = $info['dirname'] . '/_' . $info['basename'];
        $import = $suite->getRunner()->resolveFile($underscored);
      }
      $insert_code = Yaml::parseFile($import);
      array_splice($new_data, $index, 1, $insert_code);
      $needs_update = TRUE;
    }

    if ($needs_update) {
      $suite->removeAllTests();
      foreach (array_values($new_data) as $test_index => $config) {
        $suite->addTest($test_index, $config);
      }
    }
  }

}
