<?php

namespace AKlump\CheckPages\Output;

use AKlump\CheckPages\Output\Message\Message;
use AKlump\CheckPages\Traits\SerializationTrait;
use AKlump\Messaging\HasMessagesInterface;
use AKlump\Messaging\Processors\Messenger;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Use this class to add formatted messages for an HTTP request or response.
 *
 * @code
 * $logger = new HttpMessageLogger($runner->getInput(), $runner);
 * $logger($request, \AKlump\Messaging\MessageType::ERROR);
 * @endcode
 *
 * // TODO Does this need to combine with \AKlump\CheckPages\Files\HttpLogging at all?
 */
final class HttpMessageLogger {

  use SerializationTrait;

  /**
   * @var \Symfony\Component\Console\Input\InputInterface
   */
  private $input;

  /**
   * @var \AKlump\Messaging\HasMessagesInterface
   */
  private $messageBag;

  /**
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param \AKlump\Messaging\HasMessagesInterface $message_bag
   */
  public function __construct(InputInterface $input, HasMessagesInterface $message_bag) {
    $this->input = $input;
    $this->messageBag = $message_bag;
  }

  /**
   * @param \Psr\Http\Message\MessageInterface|\Psr\Http\Message\ResponseInterface $http_message
   *   The interface will determine how the messages are rewritten, either as a
   *   request or a response.
   * @param string $log_messages_type
   *
   * @return void
   */
  public function __invoke(MessageInterface $http_message, string $log_messages_type) {
    if ($http_message instanceof RequestInterface) {
      $content_verbosity = Verbosity::REQUEST;
      $url = Icons::REQUEST . $http_message->getMethod() . ' ' . $http_message->getUri();
      if (trim($url)) {
        $this->messageBag->addMessage(new Message(
          [$url],
          $log_messages_type,
          Verbosity::VERBOSE | Verbosity::REQUEST | Verbosity::HEADERS | Verbosity::RESPONSE
        ));
      }
    }
    if ($http_message instanceof ResponseInterface) {
      $content_verbosity = Verbosity::RESPONSE;
      $status_line = Icons::RESPONSE . sprintf('HTTP/%s %d %s',
          $http_message->getProtocolVersion(),
          $http_message->getStatusCode(),
          $http_message->getReasonPhrase()
        );
      $this->messageBag->addMessage(new Message(
        [$status_line],
        $log_messages_type,
        Verbosity::HEADERS | Verbosity::RESPONSE
      ));
    }

    $headers = $http_message->getHeaders();
    $headers = self::prepareHeadersMessage($headers);
    if ($headers) {
      $this->messageBag->addMessage(new Message(
        array_merge($headers, ['']),
        $log_messages_type,
        Verbosity::HEADERS
      ));
    }

    $content = (string) $http_message->getBody();
    $content_type = self::getContentType($http_message);
    $content_message = [self::prepareContentMessage($this->input, $content, $content_type)];
    if (array_filter($content_message)) {
      $this->messageBag->addMessage(new Message(array_merge($content_message, ['']), $log_messages_type, $content_verbosity));
    }
  }

  /**
   * Convert headers to message lines.
   *
   * @param array $raw_headers
   *
   * @return array
   *   An array ready for \AKlump\Messaging\MessageInterface(
   */
  private static function prepareHeadersMessage(array $raw_headers): array {
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
  private static function prepareContentMessage(InputInterface $input, string $content, string $content_type): string {
    if ($content) {
      try {
        // Make JSON content type pretty-printed for readability.
        if (strstr($content_type, 'json')) {
          $data = self::deserialize($content, $content_type);
          $content = json_encode($data, JSON_PRETTY_PRINT);
        }
      }
      catch (\Exception $exception) {
        // Purposely left blank.
      }
    }
    $content = self::truncate($input, $content);

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
  private static function truncate(InputInterface $input, string $string): string {
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
