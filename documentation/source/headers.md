# Response Header Assertions

You can assert against response headers like this:

```yaml
-
  visit: /foo
  find:
    -
      header: content-type
      contains: text/html 
```

* `header` is case-insensitive.
* If you're trying to match a header **value** with case-insensitivity, you should use the `match` key, with the `i` flag like so:
    ```yaml
    - header: content-type
      matches: /text\/html/i
    ```

See more examples in _example/tests/plugins/header.yml_
