<?php
/**
 * @file Copy over the imports files during compile.
 */

/** @var array $handler */
/** @var string $output_base_dir */

$source = $handler['path'] . '/imports';
$destination = "$output_base_dir/tests/imports";

$relative_paths = [
  "_headings.yml",
  "_interpolate.yml",
  "_links.yml",
  "find/_button.yml",
  "find/_title.yml",
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
  $data = str_replace('visit: /', 'visit: /'.\AKlump\CheckPages\CheckPages::DIR_HANDLERS.'/', $data);
  $data = str_replace('url: /', 'url: /'.\AKlump\CheckPages\CheckPages::DIR_HANDLERS.'/', $data);

  if (!file_put_contents($path, $data)) {
    return FALSE;
  }
}
