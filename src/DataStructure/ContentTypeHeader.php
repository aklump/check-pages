<?php

namespace AKlump\CheckPages\DataStructure;

use AKlump\CheckPages\Output\Message\DebugMessage;
use Laminas\Xml2Json\Xml2Json;
use Symfony\Component\Yaml\Yaml;

/**
 * A helper class to access the mime type from Content-type header
 */
final class ContentTypeHeader extends MediaTypeHeader {

  private array $values;

  /**
   * @param array|string $content_type_header_value
   */
  public function __construct($content_type_header_value) {
    parent::__construct('content-type', $content_type_header_value);
  }

  public function normalize(): self {
    return new self(array_map(function (string $value) {
      // Any media type ending in is also an XML media type. Some common
      // examples: `+xml`.  Detect that here and replace with stand so our case
      // will catch it.

      if (preg_match('/\+xml$/', $value)) {
        return 'application/xml';
      }

      // YAML-related formats.According to RFC 9512
      // [[1]](https://datatracker.ietf.org/doc/html/rfc9512), which was just
      // recently published in February 2024
      // [[2]](https://httptoolkit.com/blog/yaml-media-type-rfc/), only is
      // officially registered as a structured syntax suffix. There is no official
      // suffix. `+yaml``+yml`.  However, I'm going to be generous as there is no
      // cost to do so, and allow it for edge cases, and for better DX.
      elseif (preg_match('/\+ya?ml$/', $value)) {
        return 'application/yaml';
      }
      switch ($value) {
        case 'json':
        case 'application/json':
          return 'application/json';

        case 'application/pdf':
          // TODO What's the best solution here?
          return [];

        case 'application/yaml':
        case 'yaml':
        case 'yml':
        case 'text/yml':
        case 'text/yaml':
          return 'application/yaml';

        case 'xml':
        case 'text/xml':
        case 'application/xml':
          return 'application/xml';

        default:
          return $value;
      }
    }, $this->getMediaTypes()));
  }

}
