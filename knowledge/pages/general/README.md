<!--
title: Overview
id: readme
tags: ''
-->

# Check Pages

## Very Simple QA for HTTP

![Check Pages](../../images/check-pages.jpg)

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
-
  visit: /
  find:
    -
      not contains: '[site:name]'
```

For more code examples explore the _/examples_ directory.

{{ installer.instructions|raw }}

1. Test your installaion by typing `checkpages` in a new terminal window.
    1. If you see a Composer PHP version warning then you will need to setup an alias (see [Troubleshooting](@troubleshooting))
3. You should see the welcome screen if installation is working properly.

## Quick Start

### Initialize Your Project

1. `cd my/project/root`
2. `mkdir bin` (do this only if you want the binary(ies) to be installed here.)
3. `checkpages init`
4. Review the output for any important messages.

### Run the Demonstration Tests

1. `bin/run_check_pages_tests.sh`
2. If you are online, you should see passing tests.
3. Try again with maximim verbosity `bin/run_check_pages_tests.sh -vvv`

See [Troubleshooting](@troubleshooting) if you experiences problems.

## Documentation

<https://aklump.github.io/check-pages/>
