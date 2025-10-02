<!--
id: dev__http
tags: ''
-->

# Making HTTP Requests in Your Custom Code

Here's how to use the built-in HTTP Client. As you can see you will need a `$runner` and a `$test` instance.

```php
$http_client = new HttpClient($runner, $test);
$response = $http_client
  ->dispatchEventsWith($test)
  ->setWhyForNextRequestOnly('Lorem ipsum dolar')
  ->sendRequest(new Request('get', '/foo/bar'));
```

You can do like the following, however events will not be fired, so BE CAREFUL using this pattern.

```php
$http_client = new HttpClient($runner, $test);
$response = $http_client
  ->sendRequest(new Request('get', '/foo/bar'));
```

An example with `Cookie` header as well as getting the driver, then location.

```php
$http_client->sendRequest(new Request('get', $url, ['Cookie' => $this->getSession()->getCookieHeader()]));
$location = $this->httpClient->getDriver()->getLocation()
```

The messages can be delivered like this:

```php
$delivery = new \AKlump\CheckPages\Output\MessageDelivery();
$delivery($runner->getMessenger(), $runner->getMessages());
$runner->setMessages([]);
```
