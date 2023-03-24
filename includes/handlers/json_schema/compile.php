<?php
/**
 * @file Copy over the json_schema files during compile.
 */

/** @var array $handler */
/** @var string $output_base_dir */

$source = $handler['path']  . "/schemas/";
$destination = "$output_base_dir/tests/schemas";

if (!is_dir($destination)) {
  mkdir($destination, 0777, TRUE);
}

$schemas = glob($source . '/*.json');
foreach ($schemas as $schema) {
  $to = "$destination/" . basename($schema);
  copy($schema, $to);
}
