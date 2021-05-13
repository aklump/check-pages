<?php

namespace AKlump\CheckPages;

use AKlump\LoftLib\Code\Strings;
use JsonSchema\Validator;
use Psr\Http\Message\ResponseInterface;

final class PluginsManager implements TestPluginInterface {

  /**
   * @var array
   */
  private $testPluginsDir;

  /**
   * @var array
   */
  private $schema;

  /**
   * An array of active plugins for the current test.
   *
   * @var array
   */
  private $testPlugins = [];

  /**
   * PluginsManager constructor.
   *
   * @param string $path_to_plugins
   *   Path to the directory containing all plugins.
   */
  public function __construct(string $path_to_plugins) {
    $this->testPluginsDir = rtrim($path_to_plugins, '/');
  }

  /**
   * Set the master schema.
   *
   * @param array $schema
   *   The master schema, e.g. "schema.visit.json".
   */
  public function setSchema(array $schema) {
    $this->schema = $schema;
  }

  /**
   * Return a list of all plugins.
   *
   * @return array
   *   Each element is keyed with:
   *   - id string Plugin id.
   *   - filepath string Path to the plugin directory.
   */
  public function getAllPlugins(): array {
    $basepath = $this->testPluginsDir;
    $directory_listing = scandir($basepath);
    $plugins = [];
    foreach ($directory_listing as $basename) {
      $filepath = "$basepath/$basename";
      if ($basename !== '.' && $basename !== '..' && is_dir($filepath)) {
        $id = pathinfo($filepath, PATHINFO_FILENAME);
        $filename = Strings::upperCamel($id);
        $plugins[] = [
          'id' => $id,
          'path' => $filepath,
          'autoload' => $filepath . "/$filename.php",
          'classname' => 'AKlump\\CheckPages\\' . $filename,
        ];
      }
    }

    return $plugins;
  }

  /**
   * Delegate to all plugins the handling of an assert.
   *
   * @param \AKlump\CheckPages\Assert $assert
   * @param array $needle
   * @param \AKlump\CheckPages\ResponseInterface $response
   *
   * @return bool
   *   Return true
   */
  public function handleAssert(Assert $assert, array $needle, ResponseInterface $response) {
    $validator = new Validator();
    foreach ($this->getAllPlugins() as $plugin) {
      $plugin_schema = $this->getPluginSchema($plugin['id']);

      // Convert to an object for the validator needs it so.
      $data = json_decode(json_encode($needle));
      $validator->validate($data, $plugin_schema);

      if ($validator->isValid()) {
        // This means that this plugin's schema matches $needle, therefore this
        // plugin should handle the assert.  We will allow more than one plugin
        // to handle an assert, if it's schema matches.
        $instance = $this->getPluginInstance($plugin['id']);
        if ($instance instanceof TestPluginInterface) {
          $instance->handleAssert($assert, $needle, $response);
        }
      }
    }
  }

  /**
   * Return a new plugin instance by ID.
   *
   * @param string $plugin_id
   *   The plugin ID.
   *
   * @return \AKlump\CheckPages\TestPluginInterface
   *   A plugin instance.
   *
   * @throws \RuntimeException
   *   If the class cannot be instantiated.
   */
  private function getPluginInstance(string $plugin_id): TestPluginInterface {
    $plugins = $this->getAllPlugins();
    foreach ($plugins as $plugin) {
      if ($plugin['id'] === $plugin_id) {
        require_once $plugin['autoload'];

        return new $plugin['classname']();
      }
    }
    throw new \RuntimeException(sprintf('Could not instantiate plugin %s', $plugin_id['id']));
  }

  /**
   * Get plugin's JSON schema for a single assertion.
   *
   * @param string $plugin_id
   *   The plugin ID.
   *
   * @return array
   *   The JSON schema for a single assertion handled by this plugin.
   */
  private function getPluginSchema(string $plugin_id): array {
    $schema = $this->schema['definitions'][$plugin_id] ?? NULL;
    if (!$schema) {
      throw new \RuntimeException(sprintf('Missing schema for plugin %s', $plugin_id));
    }
    $schema['definitions'] = $this->schema['definitions'];
    unset($schema['definitions'][$plugin_id]);

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeDriver(array &$config) {
    $this->testPlugins = array_filter(array_map(function ($plugin) use ($config) {
      $plugin_schema = $this->getPluginSchema($plugin['id']);
      $validator = new Validator();
      foreach ($config['find'] as $needle) {
        $data = json_decode(json_encode($needle));
        $validator->validate($data, $plugin_schema);
        if ($validator->isValid()) {
          // This means that this plugin's schema matches $needle, therefore this
          // plugin should handle the assert.  We will allow more than one plugin
          // to handle an assert, if it's schema matches.
          $instance = $this->getPluginInstance($plugin['id']);
          if ($instance instanceof TestPluginInterface) {
            return $plugin + ['test' => $instance];
          }
        }
      }

      return NULL;
    }, $this->getAllPlugins()));

    foreach ($this->testPlugins as $plugin) {
      $plugin['test']->{__FUNCTION__}($config);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeRequest(&$driver) {
    foreach ($this->testPlugins as $plugin) {
      $plugin['test']->{__FUNCTION__}($driver);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeAssert(Assert $assert, ResponseInterface $response) {
    foreach ($this->testPlugins as $plugin) {
      $plugin['test']->{__FUNCTION__}($assert, $response);
    }

    // Reset the testPlugins since the test is over.
    // TODO Really?  Not sure about this. May 12, 2021 at 9:58:53 PM PDT, aklump.
    $this->testPlugins = [];
  }

}
