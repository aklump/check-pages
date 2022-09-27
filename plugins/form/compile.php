<?php
/**
 * @file Copy over the extra form files during compile.
 */

$source = "$plugin_dir";
$destination = "$compile_dir/web/plugins";

$relative_paths = [
  "thank_you.php",
];
foreach ($relative_paths as $relative_path) {
  $path = "$destination/$relative_path";
  $dir = dirname($path);
  if (!is_dir($dir)) {
    mkdir($dir, 0777, TRUE);
  }
  copy("$source/$relative_path", $path);

  if (!file_exists($path)) {
    return FALSE;
  }

  $path = "$destination/form.php";
  $data = file_get_contents($path);

  // The file path needs to have "plugins/" prepended too.
  $data = str_replace('action="/thank_you.php"', 'action="/plugins/thank_you.php"', $data);

  if (!file_put_contents($path, $data)) {
    return FALSE;
  }
}

$source = "$plugin_dir/imports";
$destination = "$compile_dir/tests/imports";

$relative_paths = [
  '_form_data.yml'
];
foreach ($relative_paths as $relative_path) {
  $path = "$destination/$relative_path";
  $dir = dirname($path);
  if (!is_dir($dir)) {
    mkdir($dir, 0777, TRUE);
  }
  copy("$source/$relative_path", $path);

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
