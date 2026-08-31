<?php

namespace AKlump\CheckPages\Files;

use AKlump\CheckPages\EventSubscriber\SecretsService;
use Symfony\Component\Yaml\Exception\ParseException;
use AKlump\CheckPages\Variables;

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
      $variables = new Variables();

      /**
       * Add constants to the variables.
       */
      $constants = $this->getConstants($config_path);
      foreach ($constants as $key => $value) {
        $variables->setItem($key, $value);
      }

      /**
       * Add secrets to the variables.
       */
      $directory = dirname($config_path);
      $secrets = $this->secretsService->getSecrets($directory);
      foreach ($secrets as $key => $value) {
        $variables->setItem($key, $value);
      }

      /**
       * Load and interpolate the configuration.
       */
      $config = (new LoadSerializedArray())($config_path);
      $variables->interpolate($config);
      $missing_variables = [];
      if ($variables->needsInterpolation($config, $missing_variables)) {
        throw new \UnexpectedValueException(sprintf('Configuration %s contains missing variables: %s', $unresolved_config_path, implode(', ', $missing_variables)));
      }
    }
    catch (ParseException $exception) {
      throw new \UnexpectedValueException(sprintf('Failed to load configuration from "%s" due to a parse error.', $unresolved_config_path), 0, $exception);
    }

    /**
     * Resolve any relative paths.
     */
    return (new ResolveRecursive())($config, dirname($config_path));
  }
}
