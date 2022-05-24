<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Event\DriverEventInterface;
use AKlump\CheckPages\Event\SuiteEventInterface;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\GuzzleDriver;

/**
 * Implements the Request plugin.
 */
final class Request extends LegacyPlugin {

  use \AKlump\CheckPages\SerializationTrait;

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
      ->setBody($this->getEncodedBody())
      ->setMethod($this->request['method']);
  }

  private function getEncodedBody() {
    $body = $this->request['body'];
    if (!$body || is_scalar($body)) {
      return $body;
    }

    $content_type = 'application/octet-stream';
    foreach ($this->request['headers'] as $header => $value) {
      if (strcasecmp('content-type', $header) === 0) {
        $content_type = $value;
        break;
      }
    }

    return $this->serialize($body, $content_type);
  }

  /**
   * {@inheritdoc}
   *
   * Expand request.methods into multiple tests.
   */
  public function onLoadSuite(SuiteEventInterface $event) {
    $suite = $event->getSuite();
    $items = [];
    foreach ($suite->getTests() as $test) {
      $config = $test->getConfig();
      if (isset($config['request']['methods'])) {
        $replacements = [];
        foreach ($config['request']['methods'] as $method) {

          // Create a variable that can be interpolated.
          $replacements[] = [
            'value' => $method,
            'set' => 'request.method',
          ];

          // Create the HTTP method request.
          $replacement = $config;
          $replacement['request']['method'] = $method;
          unset($replacement['request']['methods']);
          $replacements[] = $replacement;
        }
        $items[] = [$test, $replacements];
      }
    }

    foreach ($items as $item) {
      list($test, $replacements) = $item;
      $suite->replaceTestWithMultiple($test, $replacements);
    }
  }

}
