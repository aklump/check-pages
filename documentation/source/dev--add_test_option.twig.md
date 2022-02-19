<!--
id: add_test_option
-->

# Add Custom Test Options

In this example `foo` is the custom test option:

## Suite

```yaml
-
  visit: /index.html
  foo: 123
  find:
    -
      dom: h1
      is: Hello World!
```

## Runner

The `add_test_option()` function allows you to add customization at the level of your runner file.

```php
add_test_option('foo', [
  'onBeforeTest' => function ($option, \AKlump\CheckPages\Parts\Test $test, $context){
    assert($option === 123)
    // TODO Alter $test is some meaningful way.
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
