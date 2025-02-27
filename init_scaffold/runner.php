<?php
load_config('config/dev.yml');

add_mixin('http_request_files', [
  'single_file' => TRUE,
  'exclude_passing' => FALSE,
]);

run_suite('suites/*');
