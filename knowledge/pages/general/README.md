<!--
title: Overview
id: readme
tags: ''
-->

# Check Pages

## Very Simple QA for HTTP

![Check Pages](../../images/check-pages.jpg)

## Heavy Development

**Use at your own risk. This project is under heavy development and is undergoing changes pretty regularly.**

## Summary

This project intends to provide a process of QA testing of a website, which is very fast to implement and simple to maintain. You write your tests using YAML and they can be as simple as checking for a 200 HTTP response on the homepage.  PHP is working under-the-hood, but general use does not require you to know PHP.

```yaml
-
  visit: /
```

Or ensuring the admin section is protected.

```
-
  visit: /admin
  why: Make sure the `/admin` path returns 403 forbidden when not logged in.
  status: 403
```

In a third test we can assert there is one logo image on the homepage, like so:

    - visit: /
      find:
        - dom: '#logo img'
          count: 1

Lastly, make sure there are no unprocessed tokens on the page (a.k.a. a substring does not appear):

    - visit: /
      find:
        - not contains: '[site:name]'

For more code examples explore the _/examples_ directory.

**Visit <https://aklump.github.io/check_pages> for full documentation.**

## Clarity of Purpose and Limitation

The mission of this tool is to provide testing for URLS and webpages in the most simple and concise syntax possible. For testing scenarios that require element interaction, such as clicks, hovers, scrolling, etc, there are amazing projects out there such as [Cypress](https://www.cypress.io/). This project will never try to compete with that crowd, and shall always restrict it's testing of the DOM to assertions against a single snapshot of the loaded URL.

## Terms Used

* _Test Runner_ - A very simple PHP file that defines the configuration and what test suites to run, and in what order. @see _includes/runner.php_.
* _Test Suite_ - A YAML file that includes one or more checks against URLs. @see _includes/suite.yml_.
* _Test_ - A single URL check within a suite.
* _Assertion_ - A single check action against the HTTP response of a test, i.e., headers, body, status code, javascript, etc.

## Stand-alone Installation

This quickstart is to paste the below into a terminal and execute it.  It will install check pages in your home directory.

```shell
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/aklump/check_pages/refs/heads/master/setup-check-pages.sh)"
```

{{ composer.install|raw }}

* In most cases the `--dev` is appropriate, but use your own discretion.
* You will be asked if you want to create a directory for your tests when you install. This will copy over a basic scaffolding to build from.<sup>1</sup>
* More detailed examples are located in the _example_ directory.

## Example Tests Demo

If you are new to this project and would like to see a demonstration, it would be a good idea to start with the examples. Run the example tests with the following commands. Then open up the files in the _example/tests_ directory and study them to see how they work.<sup>1</sup>

1. Open a new shell window which will run the PHP server for our example test pages.

        $ ./bin/start_test_server.sh

1. Open a second shell window to execute the tests.

        $ ./bin/run_tests.sh

<sup>1</sup> If you see no _tests_ directory then create one and copy the contents of _examples_ into _tests_. The example _tests_ directory will only be created if you use `create-project` as the installation method.

### Writing Your First Test Suite

1. `checkpages init` to create tests directory and runner in the current directory.

### Multiple Configuration Files

The project is designed to be able to run the same tests using different configurations. You can create multiple configuration files so that you are able to run the same test on live and then on dev, which have different base URLs.

    .
    └── tests
        ├── config/dev.yml
        ├── config/live.yml
        ├── suite.yml
        └── runner.php

In _runner.php_ use the following line to specify the default config file:

    load_config('config/dev');

When you're ready to run this using the live config add the config filename to the CLI command, e.g.,

    $ ./checkpages runner.php --config=config/live

### Test functions

The test functions for your PHP test files are found in _includes/runner_functions.inc_.

## Is JS Supported?

Yes, not by default, but you are able to indicate that given tests requires Javascript be run. Read on...

## Javascript Testing Requirements

* Your testing machine must have Chrome installed.

## Enable Javascript per Test

Unless you enable it, or in the case the selector type (i.e., `style`
, `javascript`) requires it, javascript is not run during testing. If you need
to assert that an element exists, which was created from Javascript (or
otherwise need javascript to run on the page), you will need to indicate the
following in your test, namely `js: true`.

```yaml
-
  visit: /foo
  js: true
  find:
    -
      dom: .js-created-page-title
      text: Javascript added me to the DOM!
```

## Asserting Javascript Evaluations

Let's say you want to assert the value of the URL fragment. You can do that with
the `javascript` selector. The value of `javascript` should be the expression to
evaluate, once the page loads. Notice that you may omit the `js: true` as it
will be set automatically.

```yaml
-
  visit: /foo
  find:
    -
      javascript: location.hash
      is: "#top"
```

## Javascript Testing Related Links

* [Chrome DevTools Protocol 1.3](https://chromedevtools.github.io/devtools-protocol/1-3/)
* [Learn more](https://developers.google.com/web/updates/2017/04/headless-chrome)
* [CLI parameters](https://source.chromium.org/chromium/chromium/src/+/master:headless/app/headless_shell_switches.cc)
* [More on parameters](https://developers.google.com/web/updates/2017/04/headless-chrome#command_line_features)
* https://github.com/GoogleChrome/chrome-launcher
* <https://peter.sh/experiments/chromium-command-line-switches/>
* https://raw.githubusercontent.com/GoogleChrome/chrome-launcher/v0.8.0/scripts/download-chrome.sh

## Filter

Use `--filter` to limit which suites are run.

The value passed to the filter will be matched against the `$group/$id` of the suite. Behind the scenes it is treated as a regex pattern, if you do not include delimiters, they will be added and case will not matter.

Given the following test suites...

```text
.
├── api
│   ├── menus.yml
│   ├── reports.yml
│   └── users.yml
└── ui
    ├── footer.yml
    ├── login.yml
    └── menus.yml
```

| CLI                 | Matches                                |
|---------------------|----------------------------------------|
| `--filter=ui/`      | ui/footer.yml, ui/login.yml, menus.yml |
| `--filter=/menus`   | api/menus.yml, ui/menus.yml            |
| `--filter=ui/menus` | suites/ui/menus.yml                    |

Notice the usage of the `/` separator to control how the group influences the result.

### Complex Filter

It's possible to provide a complex filter that uses `or` logic like this:

    ./checkpages runner.php -f reports -f menus

## Troubleshooting

Try using the `--response` to see the response source code as well.

    ./checkpages runner.php --response

### Usage

In this case, since the project will be buried in your vendor directory, you will need to provide the directory path to your test files, when you run the test script, like this:

    ./vendor/bin/check_pages runner.php --dir=./tests_check_pages

This example assumes a file structure like this:

    .
    ├── tests_check_pages
    │   └── runner.php
    └── vendor
        └── bin
            └── check_pages  

## Contributing

If you find this project useful... please consider [making a donation](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4E5KZHDQCEUV8&item_name=Gratitude%20for%20aklump%2Fcheck-pages).
