<?php

namespace AKlump\CheckPages;

use Laminas\Xml2Json\Xml2Json;
use \Psr\Http\Message\ResponseInterface;
use Symfony\Component\Yaml\Yaml;

trait SerializationTrait {

  /**
   * Get content type from a response instance.
   *
   * @param \AKlump\CheckPages\Response $response
   *   A response instance.
   *
   * @return string
   *   The lower-cased content type, e.g. 'application/json'
   */
  protected function getContentType(ResponseInterface $response): string {
    $type = $response->getHeader('content-type')[0] ?? NULL;
    if (is_null($type)) {
      throw new \RuntimeException('Response is missing "Content-type" header');
    }
    [$type] = explode(';', $type . ';');

    return strtolower($type);
  }

  /**
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
  protected function deserialize(string $serial, string $type) {
    switch (strtolower($type)) {
      case 'text/html':
        return $serial;

      case 'json':
      case 'application/json':
        return json_decode($serial);

      case 'yaml':
      case 'yml':
      case 'application/x+yaml':
      case 'application/x+yml':
      case 'text/yml':
      case 'text/yaml':
        return Yaml::parse($serial);

      case 'xml':
      case 'application/xml':
        $serial = Xml2Json::fromXml($serial, TRUE);

        return json_decode($serial);
    }

    if (method_exists($this, 'debug')) {
      $this->debug($serial);
    }

    throw new \InvalidArgumentException(sprintf('Cannot deserialize content of type "%s".', $type));
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
}
