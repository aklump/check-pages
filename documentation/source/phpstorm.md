# PhpStorm Integration

You may use the _phpstorm.http_ mixin to export test suites as [HTTP Client](https://www.jetbrains.com/help/phpstorm/http-client-in-product-code-editor.html) files.

To enable this add the following to your runner:

```php
add_mixin('phpstorm.http', [
  'single_file' => TRUE,
  'exclude_passing' => TRUE,
]);
```

* Each suite will create it's own file unless you set `single_file` to `TRUE`.
* Set `exclude_passing` and only failing tests will be exported.

Each time your run the suite, the file erased and rewritten.

**Be careful with these files because they may contain session and other authentication information.**
