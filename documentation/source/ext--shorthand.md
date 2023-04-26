<!--
id: shorthand
title: Shorthand
-->

# Shorthand: Simpler, Cleaner, and More Readable

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

add_shorthand('item.delete', function ($shorthand, \AKlump\CheckPages\Parts\Test $test) {
  assert(is_numeric($shorthand));
  $config = $test->getConfig();
  $config['url'] = '/api/items';
  $config['request'] = [
    'method' => 'delete',
    'body' => json_encode([
      'id' => $shorthand
    ]),
  ];
  $config['find'] = $config['find'] ?? [];
  $config['find'][] = [
    'path' => 'result',
    'is' => 'deleted',
  ];
  $test->setConfig($config);
});
```

## An Example with Multiple Replacement Tests

```php
add_shorthand('json_factory', function ($shorthand, \AKlump\CheckPages\Parts\Test $test) use ($runner) {
  assert(is_array($shorthand));
  $config = $test->getConfig();
  $path = $runner->getFiles()->tryResolveFile($shorthand['schema'], ['json'])[0];
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

## Preserve `$shorthand` for Later

Follow this strategy if you need to keep the value of `$shorthand` in the test, for other event handlers or later processing of some sort. See [Stash](@stash) for more info.

```php
# file: runner.php

add_shorthand('foo', function ($shorthand, \AKlump\CheckPages\Parts\Test $test) {
  $config = $test->getConfig();
  $config['extras']['foo'] = $shorthand;
  $test->setConfig($config);
});
```
