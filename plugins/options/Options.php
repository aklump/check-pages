<?php

namespace AKlump\CheckPages;

use Psr\Http\Message\ResponseInterface;

/**
 * Implements the Options plugin.
 */
final class Options implements TestPluginInterface {

  const SEARCH_TYPE = 'options';

  /**
   * Holds the test suite custom options.
   *
   * @var array
   */
  private $options = [];

  /**
   * Holds the test configuration.
   *
   * @var array
   */
  private $config;

  private $pluginData = [];

  /**
   * Options constructor.
   *
   * @param \AKlump\CheckPages\CheckPages $instance
   *   The current instance.
   */
  public function __construct(CheckPages $instance) {
    $this->pluginData = ['runner' => $instance];
    $this->options = $instance->getTestOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function applies(array &$config) {
    $this->pluginData['config'] = $config;
    $this->options = array_intersect_key($this->options, $config);

    return count($this->options) > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeDriver(array &$config) {
    $this->handleCallbackByHook(__FUNCTION__, func_get_args());
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeRequest(&$driver) {
    $this->pluginData['driver'] = $driver;
    $this->handleCallbackByHook(__FUNCTION__, func_get_args());
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeAssert(Assert $assert, ResponseInterface $response) {
    $this->pluginData['assert'] = $assert;
    $this->pluginData['response'] = $response;
    $search_value = $assert->{self::SEARCH_TYPE};
    $assert->setSearch(self::SEARCH_TYPE, $search_value);
    $this->handleCallbackByHook(__FUNCTION__, func_get_args());
  }

  public function onAssertToString(string $stringified, Assert $assert): string {
    return $stringified;
  }

  /**
   * Handle the callback based on a given hook.
   *
   * @param string $hook
   * @param array $hook_args
   */
  private function handleCallbackByHook(string $hook, array $hook_args) {
    foreach ($this->options as $option) {
      if (in_array($hook, array_keys($option['hooks']))) {
        $args = array_merge(($this->pluginData['config'][$option['name']] ?? []), $hook_args, [$this->pluginData]);
        call_user_func_array($option['hooks'][$hook]['callback'], $args);
      }
    }
  }

}
