<?php

/**
 * Controller file for the request plugin example.
 */

$output = [];
$headers = array_map('strtolower', getallheaders());
$output[] = "method: " . $_SERVER['REQUEST_METHOD'];
foreach ($headers as $header => $value) {
  $output[] = "$header: $value";
}
$output[] = "body: " . file_get_contents('php://input');
print implode(PHP_EOL, $output);
exit(0);
