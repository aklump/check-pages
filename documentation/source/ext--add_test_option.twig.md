<!--
id: options
title: Options
-->

# Options: Configurable Abstraction

@todo This needs to be rethought, now that we have shorthand.

## How it Looks

```yaml
# file: suite.yml
-
  my_custom_option: 123
```

> Options are a step up from [imports](@imports) because they allow you to consolidate reusable code, while offering configuration via arguments, like a function. They are "options with arguments".

## Explained

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
  Event::TEST_CREATED => function ($option, $event){
    // Note, $option === 123
  },
]);
```

The first argument defines the option as it will be used in the suite file, e.g., `foo`. The second argument is an array of callbacks, keyed by one or more of these methods:

{% include('_event_list.md') %}

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
  Event::TEST_CREATED => function ($option, \AKlump\CheckPages\Event\TestEventInterface $event){
    list($do, $re, $mi) = $option;
    // ...
  },
  Event::REQUEST_CREATED => function ($option, \AKlump\CheckPages\Event\DriverEventInterface $event){
    list($do, $re, $mi) = $option;
    // ...
  },
]);

add_test_option('baz', [
  Event::TEST_CREATED => function ($option, \AKlump\CheckPages\Event\TestEventInterface $event){
    if ($option['lorem'] === 'ipsum dolar') {
      // ...
    }
  },
]);

run_suite('*');
```

## `Event::TEST_CREATED`

The `Event::TEST_CREATED` event is the best place to put custom processing if you want to hijack a "test". For example you could use it to set a bunch of custom variables. It's not a test, but a processor, in such a case.

## Advance to Next Test

In some cases you may want to advance to the next test after you finish executing some code inside of `Event::TEST_CREATED` in your custom test option. That is to say, you want to mark the test (option) as passed/complete and stop any further execution on that test config. To do this you should use the `\AKlump\CheckPages\Traits\PassFailTrait::setPassed()` method.

```php
add_test_option('event.create', [
  Event::TEST_CREATED => function ($option, \AKlump\CheckPages\Event\TestEventInterface $event) {
  
    // ...
    
    $event->getTest()->setPassed();
  },
]);
```
