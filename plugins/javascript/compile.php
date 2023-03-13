<?php
/**
 * @file Copy over the imports files during compile.
 */

$source = "$plugin_dir";
$destination = "$compile_dir/web/plugins";

if (!is_dir($destination)) {
  mkdir($destination, 0777, TRUE);
}
copy("$source/javascript__ajax_server.html", "$destination/javascript__ajax_server.html");
