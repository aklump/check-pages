<?php

namespace AKlump\CheckPages\Traits;

use AKlump\CheckPages\Output\DebugMessage;
use AKlump\Messaging\MessengerInterface;
use InvalidArgumentException;
use Laminas\Xml2Json\Xml2Json;
use Psr\Http\Message\MessageInterface;
use Symfony\Component\Yaml\Yaml;

trait SerializationTrait {

  /**
   * Get content type from a response instance.
   *
   * @param \Psr\Http\Message\MessageInterface $http_message
   *
   * @return string
   *   The lower-cased content type, e.g. 'application/json'
   */
  protected static function getContentType(MessageInterface $http_message): string {
    $type = $http_message->getHeader('content-type')[0] ?? NULL;
    if (empty($type)) {
      $guesser = new \AKlump\CheckPages\HttpContentTypeGuesser();
      $body = $http_message->getBody();
      $type = $guesser->guessType($body);
    }
    [$type] = explode(';', $type . ';');

    return strtolower($type);
  }

  protected static function serialize($data, string $type, MessengerInterface $printer = NULL): string {
    switch (strtolower($type)) {
      case 'text/html':
      case 'xml':
      case 'application/rss+xml':
      case 'application/xml':
        return $data;

      case 'yaml':
      case 'yml':
      case 'application/x+yaml':
      case 'application/x+yml':
      case 'text/yml':
      case 'text/yaml':
        return Yaml::dump($data);

      case 'application/x-www-form-urlencoded':
        return http_build_query($data);

      case 'json':
      case 'application/json':
        return json_encode($data);

      default:
        if ($printer) {
          array_unshift($data, 'Serialization Failure!', '');
          $printer->deliver(new DebugMessage($data));
        }
        throw new InvalidArgumentException(sprintf('Cannot serialize content of type "%s".', $type));
    }
  }

  /**
   * Deserialize a string by content type.
   *
   * XML: numeric strings are automatically cast as numbers.
   *
   * @param string $serial
   * @param string $content_type
   *
   * @return mixed|void
   *
   * @throws \InvalidArgumentException
   *   If the content type is unsupported.
   *
   * @todo Support other content types.
   */
  protected static function deserialize(string $serial, string $type, MessengerInterface $printer = NULL) {
    switch (strtolower($type)) {
      case 'text/html':
        return $serial;

      case 'application/x-www-form-urlencoded':
        parse_str($serial, $data);

        return $data;

      case 'json':
      case 'application/json':
        return json_decode($serial);

      case 'application/pdf':
        // TODO What's the best solution here?
        return [];

      case 'yaml':
      case 'yml':
      case 'application/x+yaml':
      case 'application/x+yml':
      case 'text/yml':
      case 'text/yaml':
        return Yaml::parse($serial);

      case 'xml':
      case 'application/xml':
      case 'application/rss+xml':
        $serial = Xml2Json::fromXml($serial, TRUE);
        if (!$serial) {
          return NULL;
        }
        $data = json_decode($serial, TRUE);
        $data = static::typecastNumbers($data);

        return json_decode(json_encode($data));

      default:
        if ($printer) {
          $printer->deliver(new DebugMessage([
            'Deserialization Failure!',
            '',
            $serial,
          ]));
        }

        throw new InvalidArgumentException(sprintf('Cannot deserialize content of type "%s".', $type));
    }
  }

  /**
   * Normalize a value to an array.
   *
   * @param $value
   *   One of object, array, or scalar.
   *
   * @return array
   */
  protected static function valueToArray($value): array {
    if (is_scalar($value) || is_null($value)) {
      return [$value];
    }

    return json_decode(json_encode($value), TRUE);
  }

  protected static function typecastNumbers($value) {
    if (!is_array($value)) {
      return is_numeric($value) ? $value * 1 : $value;
    }
    foreach ($value as $k => $v) {
      $value[$k] = static::typecastNumbers($v);
    }

    return $value;
  }
}
