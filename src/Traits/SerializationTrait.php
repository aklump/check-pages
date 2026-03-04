<?php

namespace AKlump\CheckPages\Traits;

use AKlump\CheckPages\DataStructure\ContentTypeHeader;
use AKlump\CheckPages\Files\HttpContentTypeGuesser;
use AKlump\CheckPages\Output\Message\DebugMessage;
use AKlump\JsonSchema\JsonDecodeLossless;
use AKlump\Messaging\MessengerInterface;
use InvalidArgumentException;
use Laminas\Xml2Json\Exception\RuntimeException;
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
    $type = (string) (new ContentTypeHeader($http_message->getHeaderLine('content-type')));
    if (empty($type)) {
      $guesser = new HttpContentTypeGuesser();
      $body = $http_message->getBody();
      $type = $guesser->guessType($body);
    }

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
    $type = (new ContentTypeHeader($type))->normalize()->get()[0];
    switch (strtolower($type)) {
      case 'text/html':
        return $serial;

      case 'application/x-www-form-urlencoded':
        parse_str($serial, $data);

        return $data;

      case 'application/json':
        //        return json_decode($serial);
        return (new JsonDecodeLossless())($serial);

      case 'application/pdf':
        // TODO What's the best solution here?
        return [];

      case 'application/yaml':
        return Yaml::parse($serial);

      case 'application/xml':
        try {
          $serial = Xml2Json::fromXml($serial, TRUE);
        }
        catch (RuntimeException $exception) {
          if (@simplexml_load_string($serial)->count() === 0) {
            // Trying to catch the result of Xml2Json processing an empty node
            // like <foo/>, which throws an exception but really is an empty
            // payload, which is not an exception, but empty JSON.
            // From the web: "The most common approach is representing it as an
            // empty object `{}`. This is what most XML-to-JSON converters use
            // by default, as it best preserves the structure while maintaining
            // valid JSON syntax."
            $serial = '{}';
          }
          else {
            throw $exception;
          }
        }
        if (!$serial) {
          return NULL;
        }

        $data = (new JsonDecodeLossless())($serial);

        return static::typecastNumbers($data);

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
    if (is_scalar($value)) {
      return is_numeric($value) ? $value * 1 : $value;
    }
    foreach ($value as &$v) {
      $v = static::typecastNumbers($v);
    }
    unset($v);

    return $value;
  }
}
