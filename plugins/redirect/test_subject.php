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

//
// This scenario is for testing multiple redirects.
//
else {
  switch ($q) {

    // Step 1
    case '':
      header("HTTP/1.1 301 Moved Permanently");
      header('Location: /redirects.php?q=/aliased-path');
      break;

    // Step 2
    case '/node/1':
      header("HTTP/1.1 302 Moved Permanently");
      header('Location: /redirects.php?q=/aliased-path');
      break;

    // Step 3
    case '/aliased-path':
      header("HTTP/1.1 200: OK");
      echo "<h1>Aliased Path</h1>";
      break;

    default:
      if (is_numeric($q)) {
        header('HTTP/1.1 ' . $q);
        break;
      }
  }
}
