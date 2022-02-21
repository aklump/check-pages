<?php

namespace AKlump\CheckPages;

use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\Parts\Test;
use Psr\Http\Message\ResponseInterface;

/**
 * Implements the Options plugin.
 */
final class Options extends Plugin {

  const SEARCH_TYPE = 'options';

  /**
   * Holds the test suite custom options.
   *
   * @var array
   */
  private $options = [];

  private $pluginData = [];

  /**
   * Options constructor.
   *
   * @param \AKlump\CheckPages\CheckPages $instance
   *   The current instance.
   */
  public function __construct(Runner $runner) {
    $this->pluginData = ['runner' => $runner];
    $this->options = $runner->getTestOptions();
    parent::__construct($runner);
  }

  /**
   * {@inheritdoc}
   */
  public function applies(array &$config) {
    $this->pluginData['config'] = $config;
    $this->options = array_intersect_key($this->options, $config);

    if (count($this->options) > 0) {
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onLoadSuite(Suite $suite) {
    $this->handleCallbackByHook(__FUNCTION__, func_get_args());
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeTest(Test $test) {
    $this->handleCallbackByHook(__FUNCTION__, func_get_args());
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

  /**
   * {@inheritdoc}
   */
  public function onAssertToString(string $stringified, Assert $assert): string {
    return $this->handleCallbackByHook(__FUNCTION__, func_get_args());
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
        $config = $this->pluginData['config'][$option['name']] ?? [];
        if (!is_array($config)) {
          $config = [$config];
        }
        $args = array_merge($config, $hook_args, [$this->pluginData]);
        try {
          call_user_func_array($option['hooks'][$hook]['callback'], $args);
        }
        catch (\Exception $exception) {
          $class = get_class($exception);
          $message = sprintf('Option "%s" failed: %s', $option['name'], $exception->getMessage());
          throw new $class($message, $exception->getCode(), $exception);
        }
      }
    }
  }

}
