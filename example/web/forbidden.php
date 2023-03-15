<?php
if (!array_key_exists('access_denied', $_GET)) {
  header("HTTP/1.1 301 Moved Permanently");
  header('Location: /forbidden.php?access_denied');
  exit(0);
}
header("HTTP/1.1 403 Forbidden");
