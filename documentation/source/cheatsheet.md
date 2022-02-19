---
id: cheatsheet
---

# Getting Started

## Does a Page Load?

```yaml
-
  visit: /foo
```

## Is the Status Code Correct?

By saying that the "page loads", we mean that it returns a status code of 200. The following is exactly the same in function as the previous example. You can assert any HTTP status code by changing the value of `expect`.

```yaml
-
  visit: /foo
  expect: 200
```

## Is an URL Redirecting As Expected?

For pages that redirect you can check for both the status code and the final location.  (`redirect` and `location` may be used interchangeably.)

```yaml
-
  visit: /moved.php
  expect: 301
  location: /location.html

-
  visit: /moved.php
  expect: 301
  redirect: /location.html
```

## Does the Page Have Certain Content?

Once loaded you can also look for things on the page with `find`. The most simple `find` assertion looks for a substring of text anywhere on the page. The following two examples are identical assertions.

```yaml
-
  visit: /foo
  find:
    - Upcoming Events Calendar
```

```yaml
-
  visit: /foo
  find:
    -
      contains: Upcoming Events Calendar
```

Ensure something does NOT appear on the page like this:

```yaml
-
  visit: /foo
  find:
    -
      not contains: "[token:123]"
```
