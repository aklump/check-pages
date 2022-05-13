<!--
id: shorthand
title: Shorthand
-->

# Shorthand: Simpler, Cleaner, and More Readible

## How it Looks

```yaml
# file: suite.yaml
-
  user: foo_user
  item.delete: 123
```

> Shorthand is a way to simplify the reading and writing of your tests.

## Explained

Without the custom shorthand, the above would have been written like this:

```yaml
# file: suite.yaml

-
  user: foo_user
  url: /api/items
  request:
    method: delete
    body: "{id:123}"
  find:
    -
      path: result
      is: deleted
```

However, by adding `item.delete` using `add_shorthand()`, we get the ability to write less code, which is less error-prone, and faster to reason about.

Here's the runner implementation:

```php
# file: runner.php

add_shorthand('item.delete', function ($config, $test) {
  assert(is_numeric($config['item.delete']));
  $config['url'] = '/api/items';
  $config['request'] = [
    'method' => 'delete',
    'body' => json_encode([
      'id' => $config['item.delete']
    ]),
  ];
  $config['find'] = $config['find'] ?? [];
  $config['find'][] = [
    'path' => 'result',
    'is' => 'deleted',
  ];
  unset($config['item.delete']);
  $test->setConfig($config);
});
```

## An Example with Multiple Replacement Tests

```php
add_shorthand('json_factory', function ($config, \AKlump\CheckPages\Parts\Test $test) use ($runner) {
  $shorthand = $config['json_factory'];
  assert(is_array($shorthand));
  $path = $runner->resolveFile($shorthand['schema']);
  $faker = new Faker($path);
  $data = $faker->jsonSerialize();

  foreach (($shorthand['values'] ?? []) as $key => $value) {
    $data[$key] = $value;
  }

  $config['is'] = json_encode($data);

  $test_configs = [];
  $test_configs[] = $config;
  $test_configs[] = [
    'set' => $config['set'] . '.validationSchema',
    'is' => json_encode($faker->getValidationSchema()),
  ];
  $test->getSuite()->replaceTestWithMultiple($test, $test_configs);
});
```
