# Testing with JavaScript

You can use JavaScript in your tests to capture things like the window location or query string. The test is run inside of Chrome devtools so the full JavaScript console is available.

## Extract an ID from an URL and Reuse it

```yaml
-
  visit: /node/25029
  find:
    -
      javascript: location.pathname.split('/')[2]
      set: nid

-
  visit: /print/node/{nid}
```

## Assert the URL Hash Matches RegEx Pattern

```yaml
-
  visit: /
  find:
    -
      javascript: location.hash
      matches: /^#foo=bar&alpha=bravo$/
```

## Functions, Literals and Promises

This is contrived, but it makes the point of how to use these constructs in a test.

```yaml
-
  url: /
  find:
    -
      why: Assert against a function
      javascript: |
        (() => {
          function getRemainingCharactersMessage(count) {
            return `${count} characters remaining.`;
          }

          return getRemainingCharactersMessage(4994);
        })();
      contains: 4994 characters remaining.

    -
      why: Assert against a literal value
      javascript: "'4994 characters remaining.'"
      contains: 4994 characters remaining.

    -
      why: Assert against a promise
      javascript: Promise.resolve('4994 characters remaining.')
      contains: 4994 characters remaining.
```

## Exceptions

JavaScript exceptions are thrown and caught by the test runner and translated to PHP exceptions.

```yaml
-
  url: /
  find:
    -
      javascript: |
        throw new Error('Test error');
```
