<!--
id: stash
-->

# Stashing Values

There is a special map key `extras` available on every test, which is ignored by the schema validator and can be used to stash/retrieve arbitrary data by handlers and extension strategies throughout the testing process (and across event handlers).

The following example is taken from a mixin that had to move it's shorthand config to the stash and then act upon those values just before the request. You can see how `extras` is used to achieve this.

It's probably not a good idea to use the `extras` key directly when writing tests, as this is may not feel as clean or clear. Better to use `add_shorthand` as shown below.

This is how the test is written.

```yaml
-
  why: 'Assert device d0 returns per schema.'
  url: /api/2.0/rest/devices/d0
  query_auth:
    count: 3
```

```php
add_shorthand('query_auth', function ($shorthand, \AKlump\CheckPages\Parts\Test $test) use ($mixin_config) {
  $test_config = $test->getConfig();
  $test_config['extras']['query_auth'] = $shorthand + $mixin_config;
  $test->setConfig($test_config);
});
```

Now the test has this structure, which will pass validation, yet still holds the data for retrieval.

```yaml
-
  why: 'Assert device d0 returns per schema.'
  url: /api/2.0/rest/devices/d0
  extras:
    query_auth:
      count: 3
```

```php
$dispatcher = $runner->getDispatcher();
$dispatcher->addListener(\AKlump\CheckPages\Event::REQUEST_CREATED, function (\AKlump\CheckPages\Event\DriverEventInterface $event) {
  $test_config = $event->getTest()->getConfig();
  if (!empty($test_config['extras']['query_auth'])) {
    query_auth_calculate($test_config, $event->getTest());
  }
});

```
