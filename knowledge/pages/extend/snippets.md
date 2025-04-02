<!--
id: ext__snippets
tags: ''
-->

# Code Snippets

This page will list out some example code you might use while extending.

## Http Request

This example shows how to make a request from the body of a _Shorthand_.

```php
add_shorthand('bundle_access', function ($shorthand, \AKlump\CheckPages\Parts\Test $test) {
  $url = $test->getSuite()->getRunner()->withBaseUrl('/foo/bar');
  $guzzle = new \AKlump\CheckPages\Browser\GuzzleDrive();
  $response = $guzzle->getClient()->get($url);
  $data = json_decode($response->getBody(), TRUE)['data'];
});
```
