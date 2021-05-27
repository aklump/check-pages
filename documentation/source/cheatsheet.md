---
id: cheatsheet
---
# Quick Reference: Test Writing

{% include('_cheatsheet.md') %}

## Check Page Loads

The most simple test involves checking that a page loads.

```yaml
- visit: /foo
```

## Check with Javascript Enabled

By default the test will not run with Javascript.  Use `js: true` to run the test with Javascript enabled.  [Learn more.](@javascript)

## Check Status Code

By saying that the "page loads", we mean that it returns a status code of 200. The following is exactly the same in function as the previous example.  You can check for any code by changing the value of `expect`.

```yaml
- visit: /foo
  expect: 200
```

## Check Redirect

For pages that redirect you can check for both the status code and the final location:

```yaml
-
  visit: /moved.php
  expect: 301
  location: /location.html
```

## Check Content

Once loaded you can also look for things on the page with `find`.  The most simple `find` assertion looks for a substring of text anywhere on the page.  The following two examples are identical assertions.

```yaml
- visit: /foo
  find:
    - Upcoming Events Calendar
```

```yaml
- visit: /foo
  find:
    -
      contains: Upcoming Events Calendar
```

Ensure something does NOT appear on the page like this:

```yaml
- visit: /foo
  find:
    -
      none: "[token:123]"
```

### Selectors
**Selectors reduce the entire page content to one or more sections.**

{% include('_selectors.md') %}

### Assertions

**Assertions provide different ways to test the page or selected section(s).**

In the case where there are multiple sections, such as multiple DOM elements, then the assertion is applied against all selections and only one must pass.

{% include('_assertions.md') %}
