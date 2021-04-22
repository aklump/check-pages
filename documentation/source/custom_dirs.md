# How To Use A Custom Directory

```php
<?php
/**
 * @file
 * Assert URLs as an anonymous user.
 */
load_config('config/dev.yml');
add_directory(realpath(__DIR__ . '/../web/themes/custom/gop_theme/components/smodal'));

run_suite('*');

// Glob doesn't yet work with add_directory(), so you have to list the suite
// explicitly like this:
run_suite('smodal');

```
