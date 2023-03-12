# The Path Plugin is for Testing Structured Content

You can also use the json_pointer plugin for JSON responses only.

```json
{
    "foo": {
        "bar": "baz"
    }
}
```

```yaml
- visit: /api/thing.json
  find:
    - path: foo.bar
      is: baz
```      

## In Depth

The plugin is able to access the values from JSON, XML and YAML responses. It uses the `Content-type` header to deserialize the request body. Given any of the following...

```text
Content-type: application/json

{"foo":{"bar":"baz"}}
```

```text
Content-type: application/xml

<?xml version="1.0" encoding="UTF-8" ?>
<foo>
  <bar>baz</bar>
</foo>
```

```text
Content-type: application/x-yaml

foo:
  bar: baz
```

... the following test could be used.

```yaml
- visit: /api/thing
  find:
    - path: foo
      count: 1
    - path: foo.bar
      is: baz
    - path: foo.bar
      is not: yaz
    - path: foo.bar
      contains: az
    - path: foo.bar
      not contains: alpha
    - path: foo.bar
      matches: /^baz$/
    - path: foo.bar
      not matches: /\d+/
```

## Request Headers

`request` can be used to send headers, as in this case where we pass `accept`. Notice how we ensure the response is encoded as JSON by the first assertion.

```yaml
- visit: /api/thing
  request:
  headers:
    accept: application/json
  find:
    - why: Assert accept header used by the server to serialize the request.
      contains: '{"bar":"baz"}'
    - path: foo.bar
      is: baz
```      

## Array Values

You can use `contains` and `count` against arrays.

Given this response:

```text
Content-type: text/yml

items:
  - apple
  - banana
  - chocolate
title: Foods
```

... this test can be used.

```yaml
- visit: /api/thing
  request:
    headers:
      accept: text/yaml
  find:
    - path: items
      count: 3
    - path: items
      count: ">1"
    - path: items
      contains: chocolate
    - path: items
      not contains: lettuce
    - path: items.1
      is: banana
```

To select the root node use an empty string for `path`...

```yaml
- visit: /api/thing
  request:
    headers:
      accept: text/yaml
  find:
    - path: ""
      count: 2
```

## Capturing Values

It can be handy to store the value for use in a subsequent test.

```yaml
- visit: /api/thing.json
  find:
    - path: foo.bar
      is: baz
      set: fooBar

- visit: /api/thing.xml
  find:
    - why: Assert both JSON and XML ship the same value.
      path: foo.bar
      is: ${fooBar}
```
