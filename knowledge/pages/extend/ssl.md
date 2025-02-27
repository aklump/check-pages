<!--
id: ex__ssl
tags: ''
-->

# Ex  Ssl

```yaml
-
  why: Test certain anonymous pages are SSL.
  url: http://www.website.com
  find:
    -
      javascript: window.location.protocol
      is: 'https:'
```
