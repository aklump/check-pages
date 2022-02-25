<?php

namespace AKlump\CheckPages;

use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\Parts\Test;
use AKlump\LoftLib\Code\Strings;
use JsonSchema\Validator;
use Psr\Http\Message\ResponseInterface;

/**
 * Handles integration of plugins with the main app.
 */
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
   * Keyed by assertion id for a given test, each value is an array of plugins.
   *
   * This maps the plugins to the given assertions, whereas $activeTestPlugins
   * maps the plugins to the test.  The distinction is important.
   *
   * @var array
   */
  private $assertionPlugins = [];

  /**
   * PluginsManager constructor.
   *
   * @param string $path_to_plugins
   *   Path to the directory containing all plugins.
   */
  public function __construct(Runner $runner_instance, string $path_to_plugins) {
    $this->runner = $runner_instance;
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

        return new $plugin['classname']($this->runner);
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
      throw new \RuntimeException(sprintf('Missing schema for plugin "%s".', $plugin_id));
    }
    $schema['definitions'] = $this->schema['definitions'];
    unset($schema['definitions'][$plugin_id]);

    return $schema;
  }

  /**
   * Tell if a plugin provides a schema or not.
   *
   * @param string $plugin_id
   *
   * @return bool
   *   True if it does.
   */
  private function hasSchema(string $plugin_id): bool {
    try {
      $this->getPluginSchema($plugin_id);

      return TRUE;
    }
    catch (\Exception $exception) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeTest(Test $test) {
    $config = $test->getConfig();
    foreach ($this->getAllPlugins() as $plugin) {
      $instance = $this->getPluginInstance($plugin['id']);
      if ($instance->applies($config)) {
        return $instance->{__FUNCTION__}($test);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeDriver(array &$config) {
    $all_plugins = $this->getAllPlugins();

    /** @var array assertionPlugins These will be captured here and used in subsequent methods. */
    $this->assertionPlugins = [];

    $this->testPlugins = [];

    foreach ($all_plugins as $plugin) {
      $instance = $this->getPluginInstance($plugin['id']);
      $applies = $instance->applies($config);
      if ($applies) {
        $this->testPlugins[$plugin['id']] = $plugin + ['instance' => $instance];
      }
      elseif (!is_bool($applies)
        && isset($config['find'])
        && $this->hasSchema($plugin['id'])) {
        if (!is_array($config['find'])) {
          $config['find'] = [$config['find']];
        }
        // We need to index each "find" element (assertion) and figure out which
        // plugin(s) should handle the assertion, if any.
        foreach ($config['find'] as $index => $assert_config) {
          $validator = new Validator();
          $data = json_decode(json_encode($assert_config));
          $plugin_schema = $this->getPluginSchema($plugin['id']);
          $validator->validate($data, $plugin_schema);
          // This means that this plugin's schema matches $assert_config, therefore this
          // plugin should handle the assert.  We will allow more than one plugin
          // to handle an assert, if it's schema matches.
          $applies = $validator->isValid() && $instance instanceof TestPluginInterface;
          if ($applies) {
            $this->assertionPlugins[$index][$plugin['id']] = $plugin + ['instance' => $instance];
            $this->testPlugins[$plugin['id']] = $plugin + ['instance' => $instance];
          }
        }
      }
    }
    foreach ($this->testPlugins as $plugin) {
      $plugin['instance']->{__FUNCTION__}($config);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeRequest(&$driver) {
    foreach ($this->testPlugins as $plugin) {
      $plugin['instance']->{__FUNCTION__}($driver);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeAssert(Assert $assert, ResponseInterface $response) {
    $assert->setToStringOverride([$this, 'onAssertToString']);
    $plugin_collection = $this->assertionPlugins[$assert->getId()] ?? [];
    foreach ($plugin_collection as $plugin) {
      $plugin['instance']->{__FUNCTION__}($assert, $response);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onAssertToString(string $stringified, Assert $assert): string {
    $plugin_collection = $this->assertionPlugins[$assert->getId()] ?? [];
    foreach ($plugin_collection as $plugin) {
      $stringified = $plugin['instance']->{__FUNCTION__}($stringified, $assert);
    }

    return $stringified;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(array &$config) {
  }

  /**
   * {@inheritdoc}
   */
  public function onLoadSuite(Suite $suite) {
    foreach ($this->getAllPlugins() as $plugin) {
      $instance = $this->getPluginInstance($plugin['id']);
      $instance->{__FUNCTION__}($suite);
    }
  }
}
