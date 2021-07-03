# Check Pages

## Very Simple QA for Websites

![Check Pages](images/check-pages.jpg)

## Summary

This project intends to provide a process of QA testing of a website, which is very fast to implement and simple to maintain. You write your tests using YAML and they can look as simple as this:

    # Check the homepage to make sure it returns 200.
    - visit: /
    
    # Make sure the `/admin` path returns 403 forbidden when not logged in.
    - visit: /admin
      expect: 403

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

## Requirements

* You must install with Composer.
* Tests suites are written in YAML.
* Little to no experience with PHP is necessary. Copy and paste will suffice.

## Install

```bash
$ composer require aklump/check-pages --dev
```

* In most cases the `--dev` is appropriate, but use your own discretion.
* You will be asked if you want to create a directory for your tests when you install. This will copy over a basic scaffolding to build from.
* More detailed examples are located in the _example_ directory.

## Example Tests Demo

If you are new to this project and would like to see a demonstration, it would be a good idea to start with the examples. Run the example tests with the following commands. Then open up the files in the _example/tests_ directory and study them to see how they work.<sup>1</sup>

1. Open a new shell window which will run the PHP server for our example test pages.

        $ ./bin/test_server.sh

1. Open a second shell window to execute the tests.

        $ ./bin/test.sh

Some failing tests are also available to explore:

    $ ./check_pages failing_tests_runner.php

<sup>1</sup> If you see no _tests_ directory then create one and copy the contents of _examples_ into _tests_. The example _tests_ directory will only be created if you use `create-project` as the installation method.

### Writing Your First Test Suite

If you created a test directory on install then you're ready to build on that.  If you did not you can do that now by running the script in _vendor/bin/check_pages_init_

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

    $ ./check_pages runner.php --config=config/live

### Test functions

The test functions for your PHP test files are found in _includes/runner_functions.inc_.

## Is JS Supported?

Yes, not by default, but you are able to indicate that given tests requires Javascript be run. Read on...

{% include('_javascript.md') %}

## Quiet Mode

To make the output much simpler, use the `--quite` flag. This will hide the assertions and reduce the output to simply pass/fail.

    ./check_pages failing_tests_runner.php --quiet

## Filter

Use the `--filter` parameter combined with a suite name to limit the runner to a single suite. This is faster than editing your runner file.

    ./check_pages runner.php --filter=page_header

## Troubleshooting

Try using the `--show-source` to see the response source code as well.

    ./check_pages failing_tests_runner.php --show-source

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

