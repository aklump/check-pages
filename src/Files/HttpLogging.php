<?php

namespace AKlump\CheckPages\Files;


use AKlump\CheckPages\DataStructure\ContentTypeHeader;
use AKlump\CheckPages\DataStructure\HttpHeader;

class HttpLogging {

  const IGNORED_HEADER_KEYS = ['host'];

  public static function request(string $title, string $method, string $url, array $request_headers = [], $request_body_or_data = NULL): string {
    if (!$url) {
      return '';
    }

    $export = [];
    $export[] = "### $title";

    // @link https://www.jetbrains.com/help/phpstorm/exploring-http-syntax.html#enable-disable-saving-cookies
    $export[] = '// @no-cookie-jar';

    $export[] = sprintf('%s %s', strtoupper($method), $url);

    foreach ($request_headers as $name => $value) {
      $header = (new HttpHeader($name, $value));
      if (!in_array($name, self::IGNORED_HEADER_KEYS)) {
        $export[] = sprintf('%s: %s', $header->getName(), $header);
      }
    }

    if (!empty($request_body_or_data)) {
      if (!is_string($request_body_or_data)) {
        $content_type = (string) (new ContentTypeHeader($request_headers['content-type'] ?? 'application/octet-stream'));
        switch ($content_type) {
          // TODO Can't we use \AKlump\CheckPages\Traits\SerializationTrait?
          case 'application/json':
            $request_body_or_data = json_encode($request_body_or_data);
            break;

          default:
            $request_body_or_data = http_build_query($request_body_or_data);
            break;
        }
      }
      $export[] = PHP_EOL . $request_body_or_data;
    }

    return implode(PHP_EOL, $export) . PHP_EOL . PHP_EOL;
  }

}
