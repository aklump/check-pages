<?php

if ('json' === pathinfo($_GET['q'])) {
  header('content-type: application/json');
}
print file_get_contents(__DIR__ . '/' . $_GET['q']);
exit(0);



