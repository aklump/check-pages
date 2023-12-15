# Value Handler

The value handler enhances interpolation by providing means to set variables in the suite.

## Test Scope

```yaml
-
  set: myVariable
  value: lorem
-
  url: /
  find:
    -
      contains: ${myVariable}  
```

That is a static example, however runtime values can be added by handlers. To do this, the handler should set `value` during or before the `\AKlump\CheckPages\Event::TEST_FINISHED` event. Once set as shown above, `${myVariable}` is available for interpolation for the remainder of the suite.

Given `MyDateHandler`, the suite could like something like this:

```yaml
-
  my_date_handler: m/d/Y
  set: formattedDate
-
  url: /
  find:
    -
      dom: .date
      contains: ${formattedDate}  
```

```php
class MyDateHandler implements \Symfony\Component\EventDispatcher\EventSubscriberInterface {
  public static function getSubscribedEvents() {
    return [
      \AKlump\CheckPages\Event::TEST_CREATED => [
        function (\AKlump\CheckPages\Event\TestEventInterface $event) {
          $test = $event->getTest();
          if ($test->has('my_date_handler')) {
            $date_format = $test->get('my_date_handler');
            $test->set('value', date($date_format));
          }
        },
      ],
    ];
  }
}
```

## Assertion Level

@todo

## Features

* Set variables from constant/interpolated values.
* Assert against constant/interpolated values.
* Works outside of HTTP requests.
