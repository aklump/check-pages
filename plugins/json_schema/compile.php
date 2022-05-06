<?php
/**
 * @file Copy over the json_schema files during compile.
 */

$source = "$plugin_dir/schemas/";
$destination = "$compile_dir/tests/schemas";

if (!is_dir($destination)) {
  mkdir($destination, 0777, TRUE);
}

$schemas = glob($source . '/*.json');
foreach ($schemas as $schema) {
  $to = "$destination/" . basename($schema);
  copy($schema, $to);
}
