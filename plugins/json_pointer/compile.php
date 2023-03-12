<?php
/**
 * @file Copy over the imports files during compile.
 */

$source = "$plugin_dir";
$destination = "$compile_dir/web/plugins";

if (!is_dir($destination)) {
  mkdir($destination, 0777, TRUE);
}
copy("$source/json_pointer__luke.json", "$destination/json_pointer__luke.json");
copy("$source/json_pointer__nested.json", "$destination/json_pointer__nested.json");
