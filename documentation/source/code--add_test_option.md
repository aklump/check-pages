<!--
version: 0.17.0
-->

# Mixin Code Example

_runner.php_

```php
add_mixin('bulk_delete');
```

_bulk_delete.php_

```php
<?php

/**
 * @file
 * Provide a test option to bulk delete resources.
 */

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\TestEventInterface;

add_test_option('bulk delete', [

  Event::TEST_CREATED => function ($resource_to_delete, TestEventInterface $event) {
    $test = $event->getTest();
    $get_collection_config = [
        'url' => sprintf('/api/2.1/json/%s', $resource_to_delete),
        'request' => ['method' => 'get'],
      ] + $test->getConfig();
    $test->setConfig($get_collection_config);
  },

  Event::REQUEST_FINISHED => function ($resource_to_delete, Event\DriverEventInterface $event) {
    $body = $event->getDriver()->getResponse()->getBody();
    $data = json_decode($body, TRUE);
    $items = $data['collection']['items'] ?? [];
    if (empty($items)) {
      return;
    }
    $test = $event->getTest();
    $test_config = $test->getConfig();

    // This will iterate on the resource collection and create a single test for
    // each, which will delete that single resource.
    foreach ($items as $item) {
      $delete_single_resource_config = bulk_delete_get_delete_config($resource_to_delete, $item['data']['id']);
      // This will add the "user" key, for example.
      $delete_single_resource_config += $test_config;
      unset($delete_single_resource_config['bulk delete']);
      $test->getSuite()->addTestByConfig($delete_single_resource_config);
    }
  },
]);

/**
 * @param string $resource
 *   The resource to delete, e.g. 'pages', 'members'.
 * @param int $id
 *   The resource ID.
 *
 * @return array
 */
function bulk_delete_get_delete_config(string $resource, int $id): array {
  return [
    'why' => sprintf('Delete %s with ID: %d', rtrim($resource, 's'), $id),
    'url' => sprintf('/api/2.1/json/%s', $resource),
    'request' => [
      'method' => 'post',
      'body' => json_encode([
        'collection' => [
          'items' => [
            [
              'action' => 'delete',
              'data' => ['id' => $id],
            ],
          ],
        ],
      ]),
    ],
  ];
}
```

_suite.yml_

```yml
-
  user: site_test_user
  bulk delete: pages
```
