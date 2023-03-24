<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Assert;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\AssertEventInterface;
use AKlump\CheckPages\Event\DriverEventInterface;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Parts\Runner;
use AKlump\LoftLib\Code\Strings;
use JsonSchema\Validator;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Handles integration of plugins with the main app.
 */
final class PluginsManager {

  private $runner;

  /**
   * @var array
   */
  private $pathToHandlers;

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
   * This maps the handlers to the given assertions, whereas $activeTestPlugins
   * maps the handlers to the test.  The distinction is important.
   *
   * @var array
   */
  private $assertionPlugins = [];

  /**
   * PluginsManager constructor.
   *
   * @param string $path_to_handlers
   *   Path to the directory containing all handlers.
   */
  public function __construct(string $path_to_handlers) {
    $this->pathToHandlers = $path_to_handlers;
  }

  public function setRunner(Runner $runner): PluginsManager {
    $this->runner = $runner;
    $this->subscribeToEvents($this->runner->getDispatcher());

    return $this;
  }

  /**
   * Set the master schema.
   *
   * @param array $schema
   *   The master schema, e.g. "schema.suite.json".
   */
  public function setSchema(array $schema) {
    $this->schema = $schema;
  }

  /**
   * Return a list of all handlers.
   *
   * @return array
   *   Each element is keyed with:
   *   - id string Plugin id.
   *   - filepath string Path to the plugin directory.
   */
  public function getAllHandlers(): array {
    $basepath = $this->pathToHandlers;
    $directory_listing = scandir($basepath);
    $plugins = [];
    foreach ($directory_listing as $basename) {
      $filepath = "$basepath/$basename";
      if ($basename !== '.' && $basename !== '..' && is_dir($filepath)) {
        $id = pathinfo($filepath, PATHINFO_FILENAME);

        // This means the plugin is disabled.
        if (strpos($id, '_') === 0) {
          continue;
        }

        $filename = Strings::upperCamel($id);
        $plugins[] = [
          'id' => $id,
          'path' => $filepath,
          'autoload' => $filepath . "/$filename.php",
          'classname' => '\\AKlump\\CheckPages\\Handlers\\' . $filename,
        ];
      }
    }

    return $plugins;
  }

  /**
   * Delegate to all the handling of an assert.
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
    foreach ($this->getAllHandlers() as $plugin) {
      $plugin_schema = $this->getPluginSchema($plugin['id']);

      // Convert to an object for the validator needs it so.
      $data = json_decode(json_encode($needle));
      $validator->validate($data, $plugin_schema);

      if ($validator->isValid()) {
        // This means that this plugin's schema matches $needle, therefore this
        // plugin should handle the assert.  We will allow more than one plugin
        // to handle an assert, if it's schema matches.
        $instance = $this->getHandlerInstance($plugin['id']);
        if ($instance instanceof LegacyPluginInterface) {
          $instance->handleAssert($assert, $needle, $response);
        }
      }
    }
  }

  /**
   * Return a new plugin instance by ID.
   *
   * @param string $handler_id
   *   The plugin ID.
   *
   * @return \AKlump\CheckPages\Plugin\LegacyPluginInterface|\Symfony\Component\EventDispatcher\EventSubscriberInterface
   *   A plugin instance.
   *
   * @throws \RuntimeException
   *   If the class cannot be instantiated.
   */
  public function getHandlerInstance(string $handler_id) {
    $handlers = $this->getAllHandlers();
    foreach ($handlers as $handler) {
      if ($handler['id'] === $handler_id) {
        require_once $handler['autoload'];

        return new $handler['classname']($this->runner);
      }
    }
    throw new \RuntimeException(sprintf('Could not instantiate plugin %s', $handler_id['id']));
  }

  /**
   * Get plugin's JSON schema for a single assertion.
   *
   * @param string $handler_id
   *   The plugin ID.
   *
   * @return array
   *   The JSON schema for a single assertion handled by this plugin.
   */
  private function getPluginSchema(string $handler_id): array {
    $schema = $this->schema['definitions'][$handler_id] ?? NULL;
    if (!$schema) {
      throw new \RuntimeException(sprintf('Missing schema for plugin "%s".', $handler_id));
    }
    $schema['definitions'] = $this->schema['definitions'];
    unset($schema['definitions'][$handler_id]);

    return $schema;
  }

  /**
   * Tell if a plugin provides a schema or not.
   *
   * @param string $handler_id
   *
   * @return bool
   *   True if it does.
   */
  private function hasSchema(string $handler_id): bool {
    try {
      $this->getPluginSchema($handler_id);

      return TRUE;
    }
    catch (\Exception $exception) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeDriver(TestEventInterface $event) {
    $config = $event->getTest()->getConfig();
    $all_plugins = $this->getAllHandlers();

    /** @var array assertionPlugins These will be captured here and used in subsequent methods. */
    $this->assertionPlugins = [];

    $this->testPlugins = [];

    foreach ($all_plugins as $plugin) {
      $instance = $this->getHandlerInstance($plugin['id']);
      if (!$instance instanceof LegacyPluginInterface) {
        continue;
      }
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
          $applies = $validator->isValid() && $instance instanceof LegacyPluginInterface;
          if ($applies) {
            $this->assertionPlugins[$index][$plugin['id']] = $plugin + ['instance' => $instance];
            $this->testPlugins[$plugin['id']] = $plugin + ['instance' => $instance];
          }
        }
      }
    }
    foreach ($this->testPlugins as $plugin) {
      $plugin['instance']->{__FUNCTION__}($event);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onAssertToString(string $stringified, Assert $assert): string {
    $plugin_collection = $this->assertionPlugins[$assert->id()] ?? [];
    foreach ($plugin_collection as $plugin) {
      $stringified = $plugin['instance']->{__FUNCTION__}($stringified, $assert);
    }

    return $stringified;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultEventHandler($event, $method) {
    foreach ($this->getAllHandlers() as $plugin) {
      $instance = $this->getHandlerInstance($plugin['id']);
      if ($instance instanceof LegacyPluginInterface) {
        $instance->$method($event);
      }
    }
  }

  /**
   * Register methods with the event dispatcher.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *
   * @return void
   */
  public function subscribeToEvents(EventDispatcherInterface $dispatcher) {
    $dispatcher->addListener(Event::SUITE_STARTED, function (Event\SuiteEventInterface $event) {
      $this->defaultEventHandler($event, 'onLoadSuite');
    });

    //    // Discover plugin methods using \AKlump\CheckPages\Event constants.
    //    $events = new \ReflectionClass(Event::CLASS);
    //    foreach ($events->getConstants() as $method => $event_name) {
    //      $method = 'on' . Strings::upperCamel(strtolower($method));
    //      foreach ($this->getAllHandlers() as $data) {
    //        $plugin_instance = $this->getHandlerInstance($data['id']);
    //        if (method_exists($plugin_instance, $method)) {
    //          $dispatcher->addListener($event_name, function ($event) use ($method, $plugin_instance) {
    //            $plugin_instance->$method($event);
    //          });
    //        }
    //      }
    //    }

    $dispatcher->addListener(Event::TEST_CREATED, function (TestEventInterface $event) {
      $config = $event->getTest()->getConfig();
      foreach ($this->getAllHandlers() as $plugin) {
        $instance = $this->getHandlerInstance($plugin['id']);
        if ($instance instanceof LegacyPluginInterface && $instance->applies($config)) {
          return $instance->onBeforeTest($event);
        }
      }
    });

    $dispatcher->addListener(Event::TEST_STARTED, [
      $this,
      'onBeforeDriver',
    ]);

    $dispatcher->addListener(Event::REQUEST_CREATED, function (DriverEventInterface $event) {
      foreach ($this->testPlugins as $plugin) {
        $plugin['instance']->onBeforeRequest($event);
      }
    });

    $dispatcher->addListener(Event::REQUEST_FINISHED, function (DriverEventInterface $event) {
      $this->defaultEventHandler($event, 'onAfterRequest');
    });

    $dispatcher->addListener(Event::ASSERT_CREATED, function (AssertEventInterface $event) {
      $assert = $event->getAssert();
      $assert->setToStringOverride([$this, 'onAssertToString']);
      $plugin_collection = $this->assertionPlugins[$assert->id()] ?? [];
      foreach ($plugin_collection as $plugin) {
        $plugin['instance']->onBeforeAssert($event);
      }
    });

    $dispatcher->addListener(Event::ASSERT_FINISHED, function (AssertEventInterface $event) {
      $this->defaultEventHandler($event, 'onAfterAssert');
    });
  }
}
