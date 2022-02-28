# PhpStorm Integration

You may use the _phpstorm.http_ mixin to export test suites as [HTTP Client](https://www.jetbrains.com/help/phpstorm/http-client-in-product-code-editor.html) files.

To enable this add the following to your runner:

```php
add_mixin('phpstorm.http', [
  'output' => config_get('files') . '/phpstorm',
  'single_file' => TRUE,
  'exclude_passing' => TRUE,
]);
```

* As you can see you have to create an output directory as `output`.
* Each suite will create it's own file unless you set `single_file` to `TRUE`.
* Set `exclude_passing` and only failing tests will be exported.

Each time your run the suite, the file will be recreated.

**Be careful with these files because they contain session cookies for any authenticated requests.**
