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

## `onBeforeTest`

The `onBeforeTest` callback is the best place to put custom processing if you want to hijack a "test". For example you could use it to set a bunch of custom variables. It's not a test, but a processor, in such a case.

## Advance to Next Test

In some cases you may want to advance to the next test after you finish executing some code inside of `onBeforeTest` in your custom test option. That is to say, you want to mark the test (option) as complete and stop any further execution on that test config. To do this you should return the value `\AKlump\CheckPages\Parts\Test::IS_COMPLETE`. This will mark the test neither passed nor failed, rather it will silently move on.

```php
add_test_option('event.create', [
  "onBeforeTest" => function ($option, \AKlump\CheckPages\Parts\Test $test, array $context) {
  
    // ...
  
    return \AKlump\CheckPages\Parts\Test::IS_COMPLETE;
  },
]);
```
