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
copy("$source/json_pointer__luke.json", "$destination/json_pointer__luke.json");
copy("$source/json_pointer__nested.json", "$destination/json_pointer__nested.json");
