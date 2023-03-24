<?php
/**
 * @file Copy over the imports files during compile.
 */

/** @var array $handler */

/** @var string $output_base_dir */

use AKlump\CheckPages\CheckPages;

$source = $handler['path'];
$destination = "$output_base_dir/web/" . CheckPages::DIR_HANDLERS;

if (!is_dir($destination)) {
  mkdir($destination, 0777, TRUE);
}
copy("$source/javascript__ajax_server.html", "$destination/javascript__ajax_server.html");
