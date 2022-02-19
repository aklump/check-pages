<!--
id: custom_dirs
-->

# How To Use A Custom Directory For Suite Files

You may locate suite files in directories other than the main one, by registering those directories with the `add_directory()` function. After that `run_suite()` will also look for suite names in the added directory(ies).

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
