<?php

namespace AKlump\CheckPages;

use AKlump\CheckPages\Output\DebugMessage;
use AKlump\Messaging\MessengerInterface;
use Laminas\Xml2Json\Xml2Json;
use Symfony\Component\Yaml\Yaml;

trait SerializationTrait {

  /**
   * Get content type from a response instance.
   *
   * @param \Psr\Http\Message\ResponseInterface|\AKlump\CheckPages\RequestDriverInterface $payload
   *
   * @return string
   *   The lower-cased content type, e.g. 'application/json'
   */
  protected function getContentType($payload): string {
    $type = $payload->getHeader('content-type')[0] ?? NULL;
    if (is_null($type)) {
      $guesser = new HttpContentTypeGuesser();
      $type = $guesser->guessType($payload);
    }
    [$type] = explode(';', $type . ';');

    return strtolower($type);
  }

  protected function serialize($data, string $type, MessengerInterface $printer = NULL): string {
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
        throw new \InvalidArgumentException(sprintf('Cannot serialize content of type "%s".', $type));
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
  protected function deserialize(string $serial, string $type, MessengerInterface $printer = NULL) {
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
        $data = $this->typecastNumbers($data);

        return json_decode(json_encode($data));

      default:
        if ($printer) {
          $printer->deliver(new DebugMessage([
            'Deserialization Failure!',
            '',
            $serial,
          ]));
        }

        throw new \InvalidArgumentException(sprintf('Cannot deserialize content of type "%s".', $type));
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
  protected function normalize($value): array {
    if (is_object($value)) {
      $value = json_decode(json_encode($value), TRUE);
    }
    if (is_array($value)) {
      return $value;
    }

    return [$value];
  }

  protected function typecastNumbers($value) {
    if (!is_array($value)) {
      return is_numeric($value) ? $value * 1 : $value;
    }
    foreach ($value as $k => $v) {
      $value[$k] = $this->typecastNumbers($v);
    }

    return $value;
  }
}
