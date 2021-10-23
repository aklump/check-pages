# Testing Response Headers

```yaml
-
  visit: /index.html
  find:
    -
      header: content-type
      contains: text/html
```
