<?php

namespace AKlump\CheckPages;

use Psr\Http\Message\ResponseInterface;

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
  public function onBeforeDriver(array &$config) {
    $this->config = $config;
    $this->request['method'] = strtoupper($config['request']['method'] ?? 'get');
    $this->request['headers'] = array_map('strtolower', $config['request']['headers'] ?? []);
    $this->request['body'] = $config['request']['body'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeRequest(&$driver) {
    if (!$driver instanceof GuzzleDriver) {
      if (!empty($this->config['js'])) {
        // Only the GuzzleDriver has been tested to work with this plugin.  When
        // JS is false the GuzzleDriver is not used.
        throw new \RuntimeException(sprintf('"js" must be set to false when using "request". %s', json_encode($this->config)));
      }
      throw new \RuntimeException(sprintf('The %s driver is not supported by the request plugin.', get_class($driver)));
    }

    foreach ($this->request['headers'] as $key => $value) {
      $driver->setHeader($key, $value);
    }
    $driver
      ->setBody($this->request['body'])
      ->setMethod($this->request['method']);
  }

}
