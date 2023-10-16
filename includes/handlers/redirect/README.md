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

# A more complex example with mixed relative and absolute URLs.
-
  loop:
    - /foo
    - /bar
    - http://my-domain.org/
    - https://my-domain.org
    - https://www.my-domain.org
-
  visit: https://www.my-domain.org/${loop.value}
  status: 301
  find:
    -
      javascript: window.location.href
      is: https://www.my-domain.org/bar
-
  end loop:     
```

## Provides Variables

`redirect.status`
`redirect.location`
