<?php

namespace AKlump\CheckPages;

use AKlump\CheckPages\Event\DriverEventInterface;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Plugin\Plugin;

/**
 * Implements the Request plugin.
 */
final class Request extends Plugin {

  /**
   * Captures the test config to share across methods.
   *
   * @var array
   */
  private $request;

  /**
   * @var array
   */
  private $config;

  /**
   * {@inheritdoc}
   */
  public function applies(array &$config) {
    return array_key_exists('request', $config);
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeDriver(TestEventInterface $event) {
    $this->config = $event->getTest()->getConfig();
    $this->request['method'] = strtoupper($this->config['request']['method'] ?? 'get');
    if (!empty($this->config['request']['headers'])) {
      $this->request['headers'] = array_combine(array_map('strtolower', array_keys($this->config['request']['headers'])), $this->config['request']['headers']);
    }
    $this->request['body'] = $this->config['request']['body'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeRequest(DriverEventInterface $event) {
    $driver = $event->getDriver();
    if (!$driver instanceof GuzzleDriver) {
      if (!empty($this->config['js'])) {
        // Only the GuzzleDriver has been tested to work with this plugin.  When
        // JS is false the GuzzleDriver is not used.
        throw new \RuntimeException(sprintf('"js" must be set to false when using "request". %s', json_encode($this->config)));
      }
      throw new \RuntimeException(sprintf('The %s driver is not supported by the request plugin.', get_class($driver)));
    }

    if (!empty($this->request['headers'])) {
      foreach ($this->request['headers'] as $key => $value) {
        $driver->setHeader($key, $value);
      }
    }
    $driver
      ->setBody($this->request['body'])
      ->setMethod($this->request['method']);
  }

}
