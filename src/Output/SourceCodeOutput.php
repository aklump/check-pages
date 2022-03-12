<?php

namespace AKlump\CheckPages\Output;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\DriverEventInterface;
use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\SerializationTrait;
use AKlump\LoftLib\Bash\Color;

/**
 * Handles output of headers, request and response.
 */
final class SourceCodeOutput {

  use SerializationTrait;

  /**
   * @var \AKlump\CheckPages\Parts\Runner
   */
  private $runner;

  /**
   * @param \AKlump\CheckPages\Parts\Runner $runner
   */
  public function __construct(Runner $runner) {
    $this->runner = $runner;

    // TODO Implement the subscriber patter; I think it's in the symfony package.
    $this->runner->getDispatcher()
      ->addListener(Event::REQUEST_CREATED, [
        $this,
        'requestOutput',
      ]);
    $this->runner->getDispatcher()
      ->addListener(Event::REQUEST_FINISHED, [
        $this,
        'responseOutput',
      ]);
  }

  /**
   * Generate output for the request.
   *
   * @param \AKlump\CheckPages\Event\DriverEventInterface $event
   *
   * @return void
   */
  public function requestOutput(DriverEventInterface $event) {
    $driver = $event->getDriver();
    $output = [];
    if ($this->runner->getInput()->getOption('show-request-headers')) {
      $headers = $this->flattenHeaders($driver->getHeaders());
      if (!empty($headers)) {
        $output[] = $headers;
      }
    }
    if ($this->runner->getInput()->getOption('show-request')) {
      $body = trim(strval($driver));
      if ($body) {
        $output[] = $body;
      }
    }
    if ($output) {
      $output = trim(implode(PHP_EOL, $output));
      $this->runner->debug($output);
    }
  }

  /**
   * Generate output for the response.
   *
   * @param \AKlump\CheckPages\Event\DriverEventInterface $event
   *
   * @return void
   */
  public function responseOutput(DriverEventInterface $event) {

    $color = $event->getTest()->hasFailed() ? 'red' : 'green';
    $request_division = Color::wrap($color, "├── RESPONSE");

    $output = [];
    $response = $event->getDriver()->getResponse();

    if ($this->runner->getInput()->getOption('show-headers')) {
      $headers = sprintf('%s/%s %d %s',
          strtoupper(parse_url($event->getTest()
            ->getAbsoluteUrl(), PHP_URL_SCHEME)),
          $response->getProtocolVersion(),
          $response->getStatusCode(),
          $response->getReasonPhrase()
        ) . PHP_EOL;
      $headers .= $this->flattenHeaders($response->getHeaders());
      if (!empty($headers)) {
        $output[] = $request_division . PHP_EOL . $headers;
      }
    }

    if ($this->runner->getInput()->getOption('show-response')) {
      $body = $response->getBody();
      if (!empty($body)) {

        // Try to make it more readable if we can.
        $content_type = $this->getContentType($response);
        if (strstr($content_type, 'json')) {
          $body = $this->deserialize($body, $content_type);
          $body = json_encode($body, JSON_PRETTY_PRINT);
        }
        if ($body) {
          if (empty($output)) {
            $output[] = $request_division . PHP_EOL;
          }
          $output[] = $body;
        }
      }
    }

    if ($output) {
      $output = trim(implode(PHP_EOL, $output));
      if ($event->getTest()->hasFailed()) {
        $this->runner->fail($output);
      }
      else {
        $this->runner->debug($output);
      }
    }
  }

  private function flattenHeaders($headers) {
    if (empty($headers)) {
      return '';
    }
    $output = [];
    foreach ($headers as $name => $value) {
      if (!is_array($value)) {
        $value = [$value];
      }
      foreach ($value as $item) {
        $output[] = sprintf('%s: %s', $name, $item);
      }
    }

    return implode(PHP_EOL, $output) . PHP_EOL;
  }

}
