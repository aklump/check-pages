<?php

namespace AKlump\CheckPages\Files;

use UnexpectedValueException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Responsible for loading and parsing configuration files in supported formats
 * (e.g., YAML, JSON) from the provided file paths.  Existing paths relative to
 * the configuration file will be resolved to absolute paths.  Non-existing,
 * relative paths will remain as they were.
 */
class LoadConfig {

  const CONFIG_EXTENSIONS = ['yml', 'yaml', 'json'];

  /**
   * @var \AKlump\CheckPages\Files\LocalFilesProvider
   */
  protected LocalFilesProvider $files;

  public function __construct(LocalFilesProvider $files) {
    $this->files = $files;
  }

  /**
   * @param string &$config_path This is an unresolved path, which will be resolved.
   *
   * @return array
   * @throws \AKlump\CheckPages\Exceptions\UnresolvablePathException If $config_path cannot be located or does not exist.
   * @throws \UnexpectedValueException If the configuration file cannot be be parsed, parses to an empty array or non-array.
   */
  public function __invoke(string &$config_path): array {
    $unresolved_config_path = $config_path;
    $config_path = $this->files->tryResolveFile($config_path, self::CONFIG_EXTENSIONS)[0];
    try {
      $extension = pathinfo($config_path, PATHINFO_EXTENSION);
      if ($extension == 'json') {
        $config = json_decode(file_get_contents($config_path), TRUE);
      }
      else {
        $config = Yaml::parseFile($config_path);
      }
      if (!$config || !is_array($config)) {
        throw new ParseException('');
      }
    }
    catch (ParseException $exception) {
      throw new UnexpectedValueException(sprintf('Failed to load configuration from "%s" due to a parse error.', $unresolved_config_path), 0, $exception);
    }

    return (new ResolveRecursive())($config, dirname($config_path));
  }

}
