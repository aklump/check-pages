<!--
id: custom_test_options
-->

# Adding New Test Options

If you need to do some fancy PHP transformations at certain points of test execution, you can hook into that flow using one or more custom test options.  **These are nothing more than functions attached to events.** In the following example, `foo` is the custom test option under study.


```yaml
# file: suite.yml
-
  visit: /index.html
  foo: 123
  find:
    -
      dom: h1
      is: Hello World!
```

The `add_test_option()` function allows you to add customization at the level of your runner file.

```php
# file: runner.php
add_test_option('foo', [
  'onBeforeTest' => function ($option, \AKlump\CheckPages\Parts\Test $test, $context){
    // Note, $option === 123
  },
]);
```

The first argument defines the option as it will be used in the suite file, e.g., `foo`. The second argument is an array of callbacks, keyed by one or more of these methods:

{% include('_callbacks.md') %}

These examples show how `$option` can have non-scalar values.

```yaml
-
  bar:
    - do
    - re
    - mi
  baz:
    lorem: ipsum dolar
```

```php
add_test_option('bar', [
  'onBeforeTest' => function ($option, \AKlump\CheckPages\Parts\Test $test, $context){
    list($do, $re, $mi) = $option;
    // ...
  },
  'onBeforeRequest' => function ($option, &$driver, array $context){
    list($do, $re, $mi) = $option;
    // ...
  },
]);

add_test_option('baz', [
  'onBeforeTest' => function ($option, \AKlump\CheckPages\Parts\Test $test, $context){
    if ($option['lorem'] === 'ipsum dolar') {
      // ...
    }
  },
]);

run_suite('*');
```
