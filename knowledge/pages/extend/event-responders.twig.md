<!--
id: events
tags: ''
-->

# Events

## Event Subscriber Services

1. Create a service class that implements `\Symfony\Component\EventDispatcher\EventSubscriberInterface`
2. Add it to _services.yml_; tag it with `{ name: event_subscriber }`
3. See the `continue` service for an example.

## All Events in Execution Order

{% include('_event_list.md') %}

## Writing a Dynamic Import

In this example a "shorthand" is built that can expand to multiple tests.

```php
respond_to(\AKlump\CheckPages\Event::SUITE_STARTED, function (\AKlump\CheckPages\Event\SuiteEventInterface $event) {
  $suite = $event->getSuite();
  foreach ($suite->getTests() as $test) {
    $config = $test->getConfig();

    // Look for the shorthand: "http.methods_not_allowed".  Remember this runs
    // before the configuration is validated, so shorthand is free to take any
    // form, and doesn't have to match the schema.
    if (isset($config['http']['methods_not_allowed'])) {


      // Now that we've found it, we will replace the "test" with the multiple
      // tests that we generate in array_map().
      $suite->replaceTestWithMultiple($test, array_map(function ($method) use ($config) {

        // This is a single, expanded-from-shorthand test configuration.
        return [
          'why' => sprintf('Assert %s returns 405: Method Not Allowed', $method),
          'url' => $config['url'],
          'request' => ['method' => $method],
          'status' => 405,
        ];
      }, $config['http']['methods_not_allowed']));
    }
  }
});
```
