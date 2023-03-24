# Testing with Javascript

You can use javascript in your tests to capture things like the window location or query string. The test is run inside of Chrome devtools so the full javascript console is available.

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
