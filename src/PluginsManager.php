<?php

namespace AKlump\CheckPages;

use AKlump\LoftLib\Code\Strings;
use JsonSchema\Validator;
use Psr\Http\Message\ResponseInterface;

final class PluginsManager implements TestPluginInterface {

  /**
   * @var array
   */
  private $pluginsDir;

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
   * Return a list of all find plugins.
   *
   * @return array
   *   Each element is keyed with:
   *   - id string Plugin id.
   *   - filepath string Path to the plugin directory.
   */
  public function getFindPlugins(): array {
    $basepath = $this->testPluginsDir . '/find';
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
    foreach ($this->getFindPlugins() as $find_plugin) {
      $plugin_schema = $this->getPluginSchema($find_plugin['id']);

      // Convert to an object for the validator needs it so.
      $data = json_decode(json_encode($needle));
      $validator->validate($data, $plugin_schema);

      if ($validator->isValid()) {
        // This means that this plugin's schema matches $needle, therefore this
        // plugin should handle the assert.  We will allow more than one plugin
        // to handle an assert, if it's schema matches.
        $instance = $this->get($find_plugin['id']);
        if ($instance instanceof TestPluginInterface) {
          $instance->handleAssert($assert, $needle, $response);
        }
      }
    }
  }

  private function get(string $plugin_id) {
    $plugins = $this->getFindPlugins();
    foreach ($plugins as $plugin) {
      if ($plugin['id'] === $plugin_id) {
        require_once $plugin['autoload'];

        return new $plugin['classname']();
      }
    }
    throw new \RuntimeException(sprintf('Could not instantiate plugin %s', $plugin_id['id']));
  }

  private function getPluginSchema($plugin_id): array {
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
    $validator = new Validator();
    $this->testPlugins = array_filter(array_map(function ($plugin) use ($config, $validator) {
      $plugin_schema = $this->getPluginSchema($plugin['id']);
      foreach ($config['find'] as $needle) {
        $data = json_decode(json_encode($needle));
        $validator->validate($data, $plugin_schema);
        if ($validator->isValid()) {
          // This means that this plugin's schema matches $needle, therefore this
          // plugin should handle the assert.  We will allow more than one plugin
          // to handle an assert, if it's schema matches.
          $instance = $this->get($plugin['id']);
          if ($instance instanceof TestPluginInterface) {
            return $plugin + ['test' => $instance];
          }
        }
      }

      return NULL;
    }, $this->getFindPlugins()));

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
