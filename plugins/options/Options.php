<?php

namespace AKlump\CheckPages;

use AKlump\CheckPages\Event\SuiteEventInterface;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Exceptions\TestOptionFailed;
use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Plugin\Plugin;

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
  public function onLoadSuite(SuiteEventInterface $event) {
    return $this->handleCallbackByHook(__FUNCTION__, func_get_args());
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeTest(TestEventInterface $event) {
    return $this->handleCallbackByHook(__FUNCTION__, func_get_args());
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeDriver(TestEventInterface $event) {
    return $this->handleCallbackByHook(__FUNCTION__, func_get_args());
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeRequest(\AKlump\CheckPages\Event\DriverEventInterface $event) {
    $this->pluginData['driver'] = $event->getDriver();

    return $this->handleCallbackByHook(__FUNCTION__, func_get_args());
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeAssert(\AKlump\CheckPages\Event\AssertEventInterface $event) {
    $assert = $event->getAssert();
    $response = $event->getDriver()->getResponse();

    $this->pluginData['assert'] = $assert;
    $this->pluginData['response'] = $response;
    $search_value = $assert->{self::SEARCH_TYPE};
    $assert->setSearch(self::SEARCH_TYPE, $search_value);

    return $this->handleCallbackByHook(__FUNCTION__, func_get_args());
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
        if (isset($this->pluginData['config'][$option['name']])) {
          $config = $this->pluginData['config'][$option['name']];
//          if (!is_array($config)) {
//            $config = [$config];
//          }
          array_unshift($hook_args, $config);
        }
        $hook_args[] = $this->pluginData;
        try {
          call_user_func_array($option['hooks'][$hook]['callback'], $hook_args);
        }
        catch (\Exception $exception) {
          // Repackage remaining exceptions as option failures.
          throw new TestOptionFailed($this->pluginData, $exception->getMessage(), $exception);
        }
      }
    }
  }

}
