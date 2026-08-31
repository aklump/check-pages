<?php

namespace AKlump\CheckPages\Files;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class LoadSerializedArray {

  public function __invoke(string $filepath): array {
    $extension = pathinfo($filepath, PATHINFO_EXTENSION);
    if ($extension == 'json') {
      $config = json_decode(file_get_contents($filepath), TRUE);
      if (json_last_error() !== JSON_ERROR_NONE) {
        throw new ParseException('Failed to parse JSON: ' . json_last_error_msg());
      }
    }
    elseif (in_array($extension, ['yml', 'yaml'])) {
      $config = Yaml::parseFile($filepath);
    }
    if (!isset($config) || !is_array($config) || empty($config)) {
      throw new ParseException(sprintf('%s must deserialize to an array', $filepath));
    }

    return $config;
  }
}
