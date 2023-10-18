# Skipping Suites

Some suites may not always be applicable, for example some suites rely on a set of content, which if not present does not indicate a failure.

For this situation you will want to indicate the that suite was skipped not that it failed.

It's simple, just use `on fail: skip suite`. Presumably you'd want to do this on the first test of the suite, however it will work on any test.

```yaml
-
  js: true
  url: /calendar
  on fail: skip suite
  request:
    timeout: 4
  find:
    -
      dom: .fc-event
```

## Related Topics

* <https://docs.phpunit.de/en/9.6/incomplete-and-skipped-tests.html#skipping-tests>
