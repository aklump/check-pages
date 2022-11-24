<?php

namespace AKlump\CheckPages\Output;

use Symfony\Component\Yaml\Yaml;

/**
 * Creat a YAML formatted message from an array dataset.
 */
class YamlMessage extends Message {

  public function __construct(
    array $dataset,
    int $yaml_level = 0,
    callable $yaml_callback = NULL,
    string $log_level = NULL,
    int $verbosity = NULL
  ) {
    $yaml = $this->datasetToYaml($dataset, $yaml_level, $yaml_callback);
    parent::__construct([$yaml], $log_level, $verbosity);
  }

  private function datasetToYaml(array $dataset, $level, callable $callback = null): string {
    for ($i = 0; $i <= $level; ++$i) {
      $dataset = [$dataset];
    }
    $yaml = explode(PHP_EOL, Yaml::dump($dataset, 4, 2));
    for ($i = 0; $i < $level; ++$i) {
      array_shift($yaml);
    }
    $yaml = implode(PHP_EOL, $yaml);
    if (is_callable($callback)) {
      $yaml = $callback($yaml);
    }

    return $yaml;
  }

}
