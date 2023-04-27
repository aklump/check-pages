# Making HTTP Requests in Your Custom Code

Here's how to use the built-in HTTP Client:

```php
$request = new \GuzzleHttp\Psr7\Request('get', '/');
$http_client = new \AKlump\CheckPages\HttpClient($runner, $runner);
$response = $http_client->sendRequest($request);
```

The messages can be delivered like this:
```php
$delivery = new \AKlump\CheckPages\Output\MessageDelivery();
$delivery($runner->getMessenger(), $runner->getMessages());
$runner->setMessages([]);
```
