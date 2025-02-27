# Check Pages

## Very Simple QA for HTTP

![Check Pages](images/check-pages.jpg)

## Heavy Development

**⚠️ Use at your own risk. This project is under heavy development and is undergoing changes pretty regularly.**

## Summary

This project intends to provide a process of QA testing of a website, which is very fast to implement and simple to maintain. You write your tests using YAML and they can be as simple as checking for a 200 HTTP response on the homepage. PHP is working under-the-hood, but general use does not require you to know PHP.

## Simple Test Syntax

Assert the homepage loads.

```yaml
-
  visit: /
```

Assert the admin section is protected.

```yaml
-
  visit: /admin
  why: Make sure the `/admin` path returns 403 forbidden when not logged in.
  status: 403
```

In a third test we can assert there is one logo image on the homepage, like so:

```yaml
-
  visit: /
  find:
    -
      dom: '#logo img'
      count: 1
```

Lastly, make sure there are no unprocessed tokens on the page (a.k.a. a substring does not appear):

```yaml
- visit: /
  find:
    - not contains: '[site:name]'
```

For more code examples explore the _/examples_ directory.

## Installation

Paste the below into a terminal and execute it. It will install check pages in your home directory. Composer is required.

```shell
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/aklump/check_pages/refs/heads/master/setup-check-pages.sh)"
```

## Documentation

https://aklump.github.io/check-pages/
