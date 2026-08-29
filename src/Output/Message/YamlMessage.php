<?php

namespace AKlump\CheckPages\Output\Message;

use AKlump\CheckPages\Output\Verbosity;
use AKlump\CheckPages\Messaging\MessageType;
use Symfony\Component\Yaml\Yaml;

/**
 * Creat a YAML formatted message from an array dataset.
 */
class YamlMessage extends Message {

  public function __construct(
    array $dataset,
    int $yaml_level = 0,
    callable $yaml_callback = NULL
  ) {
    $log_level ??= MessageType::TEST_SOURCE;
    $verbosity ??= Verbosity::VERY_VERBOSE;
    $yaml = $this->datasetToYaml($dataset, $yaml_level, $yaml_callback);
    parent::__construct([$yaml], $log_level, $verbosity);
  }

  private function datasetToYaml(array $dataset, $level, callable $callback = NULL): string {
    for ($i = 0; $i <= $level; ++$i) {
      $dataset = [$dataset];
    }
    $yaml = explode(PHP_EOL, Yaml::dump($dataset, 10, 2));
    for ($i = 0; $i < $level; ++$i) {
      array_shift($yaml);
    }
    $yaml = implode(PHP_EOL, $yaml);
    if (is_callable($callback)) {
      $yaml = $callback($yaml);
    }

    return rtrim($yaml, PHP_EOL);
  }

}
