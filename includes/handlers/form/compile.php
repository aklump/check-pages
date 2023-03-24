<?php
/**
 * @file Copy over the extra form files during compile.
 */
/** @var array $handler */

/** @var string $output_base_dir */

use AKlump\CheckPages\CheckPages;

$source = $handler['path'];
$destination = "$output_base_dir/web/" . CheckPages::DIR_HANDLERS;

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

  // The file path needs to have "handlers/" prepended too.
  $data = str_replace('action="/thank_you.php"', 'action="/' . CheckPages::DIR_HANDLERS . '/thank_you.php"', $data);

  if (!file_put_contents($path, $data)) {
    return FALSE;
  }
}

$source = $handler['path'] . "/imports";
$destination = "$output_base_dir/tests/imports";

$relative_paths = [
  '_form_data.yml',
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
  $data = str_replace('test_subject', $handler['id'], $data);

  // The file path needs to have "handlers/" prepended too.
  $data = str_replace('visit: /', 'visit: /' . CheckPages::DIR_HANDLERS . '/', $data);
  $data = str_replace('url: /', 'url: /' . CheckPages::DIR_HANDLERS . '/', $data);

  if (!file_put_contents($path, $data)) {
    return FALSE;
  }
}
