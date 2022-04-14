<?php
/**
 * @file Copy over the imports files during compile.
 */

$source = "$plugin_dir/imports";
$destination = "$compile_dir/tests/imports";

if (!is_dir($destination)) {
  mkdir($destination, 0777, TRUE);
}
copy("$source/_headings.yml", "$destination/_headings.yml");
copy("$source/_links.yml", "$destination/_links.yml");
copy("$source/_find.yml", "$destination/_find.yml");

foreach ([
           "$destination/_headings.yml",
           "$destination/_links.yml",
           "$destination/_find.yml",
         ] as $path) {
  if (!file_exists($path)) {
    return FALSE;
  }
  $data = file_get_contents($path);

  // The generic filename must be changed to the plugin ID; this is what the
  // core compiler does too.
  $data = str_replace('test_subject', $plugin['id'], $data);

  // The file path needs to have "plugins/" prepended too.
  $data = str_replace('visit: /', 'visit: /plugins/', $data);
  $data = str_replace('url: /', 'url: /plugins/', $data);

  if (!file_put_contents($path, $data)) {
    return FALSE;
  }
}
