<?php
load_config('config/live');

add_mixin('http_request_files', [
  'single_file' => TRUE,
  'exclude_passing' => FALSE,
]);

run_suite('suites/*');
