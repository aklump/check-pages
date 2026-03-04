# Making Requests Other Than GET

## Sending Data

### Request Field Types

- **URL Parameters**: `query` or `params` fields are URL-encoded in the query string (regardless of HTTP verb)
- **Request Body**: `data` or `body` fields are used for the request body (for all verbs that allow a body)

### Data Encoding

- URL parameters will always be URL-encoded (e.g., `key=value&key2=value2`)
- Request body will be encoded based on the `Content-Type` header
- If no `Content-Type` is provided, the default is `application/x-www-form-urlencoded`

```yaml

```

## Multiple Verbs

Use `methods` to request against the same endpoint using the same configuration, varying on method. This can be handly for testing REST APIs against 403 responses.

Notice how you can use `${request.method}` to interpolate.

## Custom Request Timeout This Test Only

You can set a custom timeout for this test only, which overrides `request_timeout` from the runner configuration.

```yml
-
  why: Demonstrate custom request timeout for this test only.
  visit: /test_subject.php
  request:
    timeout: 33
```
