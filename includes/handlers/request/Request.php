<?php

namespace AKlump\CheckPages\Handlers;

use AKlump\CheckPages\Browser\GuzzleDriver;
use AKlump\CheckPages\Event\DriverEventInterface;
use AKlump\CheckPages\Event\SuiteEventInterface;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Output\DebugMessage;
use AKlump\CheckPages\Plugin\LegacyPlugin;
use AKlump\CheckPages\SerializationTrait;
use AKlump\Messaging\MessageType;

/**
 * Implements the Request handler.
 */
final class Request extends LegacyPlugin {

  use SerializationTrait;

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
    $test = $event->getTest();
    $this->config = $test->getConfig();
    $test->interpolate($this->config['request']);
    $test->setConfig($this->config);

    $this->request['method'] = strtoupper($this->config['request']['method'] ?? 'get');
    $headers = $this->config['request']['headers'] ?? [];
    if ($headers) {
      $headers = array_change_key_case($headers);
      $headers = array_filter($headers, function ($value) {
        return !empty($value);
      });
      $headers = array_map('strval', $headers);
      $this->request['headers'] = $headers;
    }
    $this->request['body'] = $this->config['request']['body'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeRequest(DriverEventInterface $event) {
    $driver = $event->getDriver();
    $request_config = $event->getTest()->getConfig()['request'] ?? [];
    $request_config_keys = array_keys($request_config);

    if (!$driver instanceof GuzzleDriver) {
      if ($request_config_keys === ['timeout']
        || ($request_config_keys === [
            'method',
            'timeout',
          ] && $request_config_keys['method'] === 'get')) {
        // Allow this combination for any driver because all it's doing is
        // altering the request timeout and the assumption is that all drivers
        // will work with GET.
      }
      elseif (!empty($this->config['js'])) {
        // Only the GuzzleDriver has been tested to work with this handler.  When
        // JS is false the GuzzleDriver is not used.
        throw new \RuntimeException(sprintf('"js" must be set to false when using "request". %s', json_encode($this->config)));
      }
      else {
        throw new \RuntimeException(sprintf('The %s driver is not supported by the request handler.', get_class($driver)));
      }
    }

    if (isset($request_config['timeout']) && is_numeric($request_config['timeout'])) {
      $driver->setRequestTimeout($request_config['timeout']);
    }

    if (!empty($this->request['headers'])) {
      foreach ($this->request['headers'] as $key => $value) {
        $driver->setHeader($key, $value);
      }
    }

    $encoded_body = $this->getEncodedBody();
    $driver
      ->setBody($encoded_body)
      ->setMethod($this->request['method']);

    // Interpolation check debugging.  This step will look for un-interpolated
    // values in the request and send out a debug message, which may help
    // developers troubleshoot failing tests.
    $interpolation_review = [
      'method' => $this->request['method'],
      'headers' => $this->request['headers'] ?? [],
      'body' => $encoded_body,
    ];
    if ($event->getTest()->getSuite()->variables()
      ->needsInterpolation($interpolation_review)) {
      $event->getTest()
        ->addMessage(new DebugMessage([
          'Check variables; the request appears to still need interpolation.',
        ], MessageType::DEBUG));
    }

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
