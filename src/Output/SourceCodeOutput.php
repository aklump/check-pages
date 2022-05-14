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

  const INDENT = '│   ';

  const COLOR_REQUEST = 'green';

  const COLOR_RESPONSE = 'yellow';

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

    // TODO Implement the subscriber pattern; I think it's in the symfony package.
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
    $this->runner->getDispatcher()
      ->addListener(Event::TEST_FINISHED, [
        $this,
        'handleTestResults',
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
    $test = $event->getTest();
    $input = $test->getRunner()->getInput();
    $config = $test->getConfig();
    $show_url = !empty($config['url']) && $test->getRunner()
        ->getOutput()
        ->isVerbose();
    $show_headers = $input->getOption('request') || $input->getOption('req-headers');
    $show_body = $input->getOption('request') || $input->getOption('req');

    if ($show_url) {
      $url = $this->indent($test->getHttpMethod() . ' ' . $test->getAbsoluteUrl());
      Feedback::$requestUrl->overwrite(Color::wrap(Feedback::COLOR_PENDING, $url));
    }

    if ($show_headers) {
      $headers = $this->flattenHeaders($driver->getHeaders());
      if ($headers) {
        $headers = $this->indent($headers);
        Feedback::$requestHeaders->overwrite([
          Color::wrap(SourceCodeOutput::COLOR_REQUEST, $headers),
        ]);
      }
    }
    if ($show_body) {
      $body = trim(strval($driver));
      if ($body) {
        $body = $this->indent($body);
        Feedback::$requestBody->overwrite([
          Color::wrap(SourceCodeOutput::COLOR_REQUEST, $body),
          $this->indent(''),
        ]);
      }
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

    $test = $event->getTest();
    $color = $test->hasFailed() ? 'red' : 'green';
    $request_division = Color::wrap($color, "├── RESPONSE");
    $input = $test->getRunner()->getInput();
    $show_headers = $input->getOption('response') || $input->getOption('headers');
    $show_body = $input->getOption('response') || $input->getOption('res');
    $output = [];
    $response = $event->getDriver()->getResponse();

    if ($show_headers) {
      $headers = sprintf('%s/%s %d %s',
          strtoupper(parse_url($event->getTest()
            ->getAbsoluteUrl(), PHP_URL_SCHEME)),
          $response->getProtocolVersion(),
          $response->getStatusCode(),
          $response->getReasonPhrase()
        ) . PHP_EOL;
      $headers .= $this->flattenHeaders($response->getHeaders());
      if (!empty($headers)) {
        $output[] = $request_division . PHP_EOL . $this->indent($headers);
      }
    }

    if ($show_body) {
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
        $this->runner->info($output);
      }
    }
  }

  /**
   * Handle test results.
   *
   * When a test fails the full-request will be displayed, regardless of parameters.
   *
   * @param \AKlump\CheckPages\Event\DriverEventInterface $event
   *
   * @return void
   */
  public function handleTestResults(DriverEventInterface $event) {
    $test = $event->getTest();
    $driver = $event->getDriver();
    $url = $test->getHttpMethod() . ' ' . $test->getAbsoluteUrl();
    if ($test->hasFailed()) {
      Feedback::$requestUrl->overwrite([
        Color::wrap('red', $url),
      ]);

      $headers = $this->flattenHeaders($driver->getHeaders());
      if ($headers) {
        Feedback::$requestHeaders->overwrite([
          Color::wrap('red', $headers),
        ]);
      }

      $body = trim(strval($driver));
      if ($body) {
        Feedback::$requestBody->overwrite([
          Color::wrap('red', $body),
          '',
        ]);
      }
    }
    else {
      if ($event->getTest()->getRunner()->getOutput()->isVeryVerbose()) {
        Feedback::$requestUrl->overwrite([
          Color::wrap('green', $this->indent($url)),
        ]);
      }
      elseif ($event->getTest()->getRunner()->getOutput()->isVerbose()) {
        Feedback::$requestUrl->clear();
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

  private function indent(string $string) {
    return implode(PHP_EOL, array_map(function ($line) {
      return self::INDENT . $line;
    }, explode(PHP_EOL, $string)));
  }
}
