<?php

namespace AKlump\CheckPages\Output;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\DriverEventInterface;
use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\SerializationTrait;
use AKlump\Messaging\MessageType;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Input\InputInterface;

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
        'onRequestTestFinished',
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
    $show = new VerboseDirective(strval($input->getOption('show')));

    $url = $test->getHttpMethod() . ' ' . $test->getAbsoluteUrl();
    if (trim($url)) {
      $test->addMessage(new Message([
        $url,
      ], MessageType::INFO, Verbosity::VERBOSE | Verbosity::REQUEST | Verbosity::HEADERS));
    }

    if ($show->showSendHeaders()) {
      $headers = $driver->getHeaders();
      $headers = $this->prepareHeadersMessage($headers);
      if ($headers) {
        $test->addMessage(new Message(
          array_merge($headers, ['']),
          MessageType::DEBUG,
          Verbosity::HEADERS
        ));
      }
    }

    $body = $this->prepareContentMessage($input, strval($driver), $this->getContentType($driver));
    if ($body) {
      $test->addMessage(new Message([
        $body,
        '',
      ], MessageType::DEBUG, Verbosity::REQUEST));
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
    $driver = $event->getDriver();
    $test = $event->getTest();
    $input = $test->getRunner()->getInput();
    $response = $event->getDriver()->getResponse();

    $show = new VerboseDirective(strval($input->getOption('show')));
    $show_headers = $show->showResponseHeaders();

    if ($show_headers) {
      $headers = $response->getHeaders();
      $headers = $this->prepareHeadersMessage($headers);
      if ($headers) {
        $test->addMessage(new Message(
          array_merge($headers, [''])
          , MessageType::DEBUG,
          Verbosity::HEADERS
        ));
      }
    }

    $lines = [];
    $lines[] = $this->getResponseHttpStatusLine($test, $response);
    $lines[] = $this->prepareContentMessage($input, $response->getBody(), $this->getContentType($driver));
    if (array_filter($lines)) {
      $test->addMessage(new Message(array_merge($lines, ['']), MessageType::DEBUG, Verbosity::RESPONSE));
    }
  }

  /**
   * Handle test results.
   *
   * When a test fails the request info will always be shown.
   *
   * @param \AKlump\CheckPages\Event\DriverEventInterface $event
   *
   * @return void
   */
  public function onRequestTestFinished(DriverEventInterface $event) {
    $test = $event->getTest();
    if ($test->hasPassed()) {
      return;
    }

    $driver = $event->getDriver();
    $input = $test->getRunner()->getInput();

    //
    // Request
    //
    $url = $test->getHttpMethod() . ' ' . $test->getAbsoluteUrl();
    if (trim($url)) {
      $test->addMessage(new Message([
        $url,
      ], MessageType::ERROR));
    }

    $headers = $driver->getHeaders();
    $headers = $this->prepareHeadersMessage($headers);
    if ($headers) {
      $test->addMessage(new Message(
        array_merge($headers, ['']),
        MessageType::ERROR,
        Verbosity::HEADERS
      ));
    }

    $body = $this->prepareContentMessage($input, strval($driver), $this->getContentType($driver));
    if ($body) {
      $test->addMessage(new Message([
        $body,
        '',
      ], MessageType::DEBUG,
        Verbosity::REQUEST
      ));
    }

    //
    // Response
    //
    $response = $event->getDriver()->getResponse();

    $headers = $response->getHeaders();
    $headers = $this->prepareHeadersMessage($headers);
    if ($headers) {
      $test->addMessage(new Message(
        array_merge($headers, ['']),
        MessageType::ERROR,
        Verbosity::HEADERS
      ));
    }

    $lines = [];
    $lines[] = $this->getResponseHttpStatusLine($test, $response);
    $lines[] = $this->prepareContentMessage($input, $response->getBody(), $this->getContentType($driver));
    if (array_filter($lines)) {
      $test->addMessage(new Message(array_merge($lines, ['']), MessageType::DEBUG, Verbosity::RESPONSE));
    }
  }

  /**
   * @param \AKlump\CheckPages\Parts\Test $test
   * @param \Psr\Http\Message\ResponseInterface $response
   *
   * @return string
   *   A string that shows the response info.
   */
  private function getResponseHttpStatusLine(Test $test, ResponseInterface $response) {
    return sprintf('%s/%s %d %s',
      strtoupper(parse_url($test->getAbsoluteUrl(), PHP_URL_SCHEME)),
      $response->getProtocolVersion(),
      $response->getStatusCode(),
      $response->getReasonPhrase()
    );
  }

  /**
   * Convert headers to message lines.
   *
   * @param array $raw_headers
   *
   * @return array
   *   An array ready for \AKlump\Messaging\MessageInterface(
   */
  private function prepareHeadersMessage(array $raw_headers): array {
    $raw_headers = array_filter($raw_headers);
    if (empty($raw_headers)) {
      return [];
    }

    $lines = [];
    foreach ($raw_headers as $name => $value) {
      if (!is_array($value)) {
        $value = [$value];
      }
      foreach ($value as $item) {
        $lines[] = sprintf('%s: %s', $name, $item);
      }
    }

    return $lines;
  }

  /**
   * Format content per content type for message lines.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param string $content
   * @param string $content_type
   *
   * @return string
   *   An array ready for \AKlump\Messaging\MessageInterface
   */
  private function prepareContentMessage(InputInterface $input, string $content, string $content_type): string {
    $content = $this->truncate($input, $content);
    if ($content) {
      try {
        // Make JSON content type pretty-printed for readability.
        if (strstr($content_type, 'json')) {
          $data = $this->deserialize($content, $content_type);
          $content = json_encode($data, JSON_PRETTY_PRINT);
        }
      }
      catch (\Exception $exception) {
        // Purposely left blank.
      }
    }

    return $content;
  }

  /**
   * Truncate $string when appropriate.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param string $string
   *
   * @return string
   *   The truncated string.
   */
  private function truncate(InputInterface $input, string $string): string {
    $string = trim($string);
    if ($string) {
      $length = $input->getOption('truncate');
      if ($length > 0 && strlen($string) > $length) {
        return substr($string, 0, $length) . '...';
      }
    }

    return $string;
  }

}
