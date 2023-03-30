# Redirect

Provides asserting against the redirect location.

For pages that redirect you can check for both the status code and the final location.  (`redirect` and `location` may be used interchangeably.)

```yaml
-
  visit: /moved.php
  status: 301
  location: /location.html

-
  visit: /moved.php
  status: 301
  redirect: /location.html
```

## Provides Variables

`redirect.status`
`redirect.location`
