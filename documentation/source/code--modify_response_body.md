# How to Modify a Response Body

This takes a JSON response and filters some values of a certain arrays. This was implemented as a mixin.

_mixins/filter_alerts.php_

```php
<?php

namespace AKlump\CheckPages\Mixins\FilterAlerts;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\SerializationTrait;

const NAME = 'filter_alerts';

/**
 * Remove user alerts given by "filter_alerts" from response.
 *
 * @code
 *  -
 *    why: Assert there are no alerts to start with.
 *    user: site_test.member
 *    url: /api/2.1/json/user
 *    filter_alerts: [user_alerts_block]
 *    find:
 *      -
 *        pointer: /collection/items/0/data/alerts
 *        count: 0
 * @endcode
 *
 * @see \AKlump\CheckPages\Mixins\FilterAlerts\NAME
 */
respond_to([
  Event::ASSERT_CREATED,
  1000,
], function (Event\AssertEventInterface $event) {
  $assert = $event->getAssert();
  $test = $assert->getTest();
  if (!$test->has(NAME)) {
    return;
  }
  $filter_types = $assert->getTest()->get(NAME);
  $json = $assert->getHaystack()[0];
  $filtered_json = (new FilterAlertTypes())($json, $filter_types);
  $assert->setHaystack([$filtered_json]);
});

class FilterAlertTypes {

  use SerializationTrait;

  public function __invoke(string $json, array $filter_types): string {
    $haystack = static::deserialize($json, 'application/json');
    if (!isset($haystack->collection->items[0]->data)) {
      return $json;
    }
    $alerts = &$haystack->collection->items[0]->data->alerts;
    $alerts = array_filter($alerts, function ($item) use ($filter_types) {
      if (!is_object($item) && !is_array($item)) {
        return TRUE;
      }
      $type = is_object($item) ? $item->type : $item['type'];

      return !in_array($type, $filter_types);
    });
    $alerts = array_values($alerts);

    return json_encode($haystack);
  }
}

```
