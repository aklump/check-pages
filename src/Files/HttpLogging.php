<?php

namespace AKlump\CheckPages\Files;


class HttpLogging {

  public static function request(string $title, string $method, string $url, array $request_headers = [], $request_body_or_data = NULL): string {
    if (!$url) {
      return '';
    }

    $export = [];
    $export[] = "### $title";

    // @link https://www.jetbrains.com/help/phpstorm/exploring-http-syntax.html#enable-disable-saving-cookies
    $export[] = '// @no-cookie-jar';

    $export[] = sprintf('%s %s', strtoupper($method), $url);

    $request_headers = array_change_key_case($request_headers);
    foreach ($request_headers as $key => $value) {
      $export[] = sprintf('%s: %s', $key, $value);
    }

    if (!empty($request_body_or_data)) {
      $content_type = $request_headers['content-type'] ?? 'application/octet-stream';
      if (!is_string($request_body_or_data)) {
        switch ($content_type) {
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
