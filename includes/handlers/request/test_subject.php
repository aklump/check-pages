<?php

/**
 * Controller file for the request plugin example.
 */

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'GET', 'PUT'])) {
  header('HTTP/1.0 405 Method Not Allowed');
  exit();
}

$output = [];
$headers = array_change_key_case(getallheaders());
$output[] = "method: " . $_SERVER['REQUEST_METHOD'];
foreach ($headers as $header => $value) {
  $output[] = "$header: $value";
}
$output[] = "body: " . file_get_contents('php://input');
print implode(PHP_EOL, $output);

if (isset($_GET['timeout'])) {
  // This will allow the tests to assert timeouts.
  sleep($_GET['timeout']);
}

exit(0);
