---
id: cheatsheet
title: Cheatsheet
---
# Test Writing Cheatsheet

The most simple test involves checking that a page response with a status code.  The following two examples are identical.  The default expectation is HTTP 200.  However you can check for any code using `expect`.

## Check Status Code

```yaml
- url: /foo
```
```yaml
- url: /foo
  expect: 200
```

## Check Content

Once loaded you can also look for things on the page with `find`.  The most simple `find` assertion looks for a substring of text anywhere on the page.  The following two examples are identical assertions.

```yaml
- url: /foo
  find:
    - Upcoming Events Calendar
```

```yaml
- url: /foo
  find:
    -
      contains: Upcoming Events Calendar
```

### Selectors
**Selectors reduce the entire page content to one or more sections.**

{% include('_selectors.md') %}

### Assertions

**Assertions provide different ways to test the page or selected section(s).**

{% include('_assertions.md') %}
