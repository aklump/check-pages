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

  // TODO Replace with \AKlump\CheckPages\Parts\Suite::replaceTestWithMultiple
  public function onLoadSuite(SuiteEventInterface $event) {
    $suite = $event->getSuite();
    foreach ($suite->getTests() as $test) {
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
      $suite->replaceTestWithMultiple($test, $insert_code);
    }
  }

}
