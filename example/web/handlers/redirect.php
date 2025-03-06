<?php
/**
 * Demonstrates multiple redirects.
 */

$q = $_GET['q'] ?? '';

//
// This scenario is for testing URL encoding problems.
//
if ('/study-guides' === $q) {
  header("HTTP/1.1 301 Moved Permanently");
  header('Location: /library?f[0]=story_type%3A1241&f[1]=story_type%3A1242');
}
elseif ('/login' === $q) {
  header("HTTP/1.1 301 Moved Permanently");
  header('Location: /handlers/redirect.php?q=#sm=login');
}

//
// This scenario is for testing multiple redirects.
//
else {
  switch ($q) {

    // Step 1
    case '':
      header("HTTP/1.1 301 Moved Permanently");
      // Note: this one is important because it covers if when the location
      // header is absolute, we can still use a relative path in the the test.
      $base_url = sprintf('%s://%s', $_SERVER['HTTPS'] ?? 'http', rtrim($_SERVER['HTTP_HOST'], '/'));
      header("Location: $base_url/handlers/redirect.php?q=/aliased-path");
      break;

    // Step 2
    case '/node/1':
      header("HTTP/1.1 302 Moved Permanently");
      header('Location: /handlers/redirect.php?q=/aliased-path');
      break;

    // Step 3
    case '/aliased-path':
      header("HTTP/1.1 200 OK");
      echo "<h1>Aliased Path</h1>";
      break;

    default:
      if (is_numeric($q)) {
        header('HTTP/1.1 ' . $q);
        break;
      }
  }
}
