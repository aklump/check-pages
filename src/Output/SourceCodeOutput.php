<?php

namespace AKlump\CheckPages\Output;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\DriverEventInterface;
use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\SerializationTrait;
use AKlump\LoftLib\Bash\Color;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;

/**
 * Handles output of headers, request and response.
 */
final class SourceCodeOutput {

  const INDENT = '    ';

  const COLOR_REQUEST = 'purple';

  const COLOR_RESPONSE = 'black';

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

    $show = new VerboseDirective(strval($input->getOption('show')));
    $show_headers = $show->showSendHeaders();
    $show_body = $show->showSendBody();
    $show_url = $show_headers || $test->getRunner()->getOutput()->isVerbose();

    if (!empty($config['url']) && $show_url) {
      $url = $this->indent($test->getHttpMethod() . ' ' . $test->getAbsoluteUrl());
      Feedback::$requestUrl->overwrite(Color::wrap(Feedback::COLOR_PENDING, $url));
    }

    if ($show_headers) {
      $this->overwriteHeaders($input, Feedback::$requestHeaders, $driver->getHeaders(), self::COLOR_REQUEST);
    }

    if ($show_body) {
      $this->overwriteBody($input, Feedback::$requestBody, strval($driver), $this->getContentType($driver), self::COLOR_REQUEST);
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
    $input = $test->getRunner()->getInput();

    $show = new VerboseDirective(strval($input->getOption('show')));
    $show_headers = $show->showResponseHeaders();
    $show_body = $show->showResponseBody();

    $response = $event->getDriver()->getResponse();

    if ($show_headers) {
      $this->overwriteHeaders($input, Feedback::$responseHeaders, $response->getHeaders(), self::COLOR_RESPONSE, $test, $response);
    }

    if ($show_body) {
      $this->overwriteBody($input, Feedback::$responseBody, $response->getBody(), $this->getContentType($response), self::COLOR_RESPONSE);
    }
  }

  /**
   * Overwrite headers with proper processing and formatting to a section.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param \Symfony\Component\Console\Output\ConsoleSectionOutput $section
   * @param array $headers
   * @param string $color
   *
   * @return void
   */
  private function overwriteHeaders(InputInterface $input, ConsoleSectionOutput $section, array $headers, string $color, Test $test = NULL, $response = NULL) {
    $headers = array_filter($headers);
    if (empty($headers)) {
      $section->clear();

      return;
    }

    $line = '';
    if ($response) {
      $line .= $this->getResponseHttpStatusLine($test, $response) . PHP_EOL;
    }
    $line .= $this->flattenHeaders($headers);
    if ($line) {
      $section->overwrite([
        Color::wrap($color, $this->indent($this->truncate($input, $line))),
        Color::wrap($color, $this->indent('')),
      ]);
    }
  }

  /**
   * Overwrite body with proper processing and formatting to a section.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param \Symfony\Component\Console\Output\ConsoleSectionOutput $section
   * @param string $body
   * @param string $content_type
   * @param string $color
   *
   * @return void
   */
  private function overwriteBody(InputInterface $input, ConsoleSectionOutput $section, string $body, string $content_type, string $color) {
    $body = $this->truncate($input, $body);
    if (!$body) {
      return;
    }

    // Try to make it more readable if we can.
    $data = $this->deserialize($body, $content_type);
    if (strstr($content_type, 'json')) {
      $body = json_encode($data, JSON_PRETTY_PRINT);
    }

    $section->overwrite([
      Color::wrap($color, $this->indent($body)),
      Color::wrap($color, $this->indent('')),
    ]);
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
    $input = $event->getTest()->getRunner()->getInput();
    $output = $event->getTest()->getRunner()->getOutput();
    $url = $test->getHttpMethod() . ' ' . $test->getAbsoluteUrl();

    $status = $this->getResponseHttpStatusLine($test, $driver->getResponse());

    if ($test->hasFailed()) {
      //      Feedback::$requestUrl->overwrite([
      //        Color::wrap('red', $this->indent($url)),
      //      ]);
      //
      //      Feedback::$testResult->overwrite([
      //        Color::wrap('red', '└── ' . $status),
      //        '',
      //      ]);

      // REQUEST
      //      $this->overwriteHeaders($input, Feedback::$requestHeaders, $driver->getHeaders(), 'red');
      //      $this->overwriteBody($input, Feedback::$requestBody, strval($driver), $this->getContentType($driver), 'red');

      // RESPONSE
      //      $response = $event->getDriver()->getResponse();
      //      $this->overwriteHeaders($input, Feedback::$responseHeaders, [], 'red', $test, $response);
      //      $this->overwriteBody($input, Feedback::$responseBody, $response->getBody(), $this->getContentType($response), 'red');
    }
    else {

      if ($output->isVeryVerbose()) {
        Feedback::$requestUrl->overwrite([
          Color::wrap('green', $this->indent($url)),
        ]);

        // Keeping this at -vv because -v with "Passed" seems nice.
        Feedback::$testResult->overwrite([
          Color::wrap('green', '└── ' . $status),
          '',
        ]);
      }
      elseif ($output->isVerbose()) {
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

  private function truncate(InputInterface $input, string $string): string {
    $string = trim($string);
    $length = $input->getOption('truncate');
    if ($length > 0 && strlen($string) > $length) {
      return substr($string, 0, $length) . '...';
    }

    return $string;
  }

  private function getResponseHttpStatusLine(Test $test, \Psr\Http\Message\ResponseInterface $response) {
    return sprintf('%s/%s %d %s',
      strtoupper(parse_url($test->getAbsoluteUrl(), PHP_URL_SCHEME)),
      $response->getProtocolVersion(),
      $response->getStatusCode(),
      $response->getReasonPhrase()
    );
  }
}
