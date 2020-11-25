# Check Pages

This project intends to provide a process of QA testing of a website, which is very fast to implement and simple to maintain.  You write your tests using YAML and they can look as simple as these two tests:

    - url: /
    - url: /admin
      expect: 403

The first test will check the homepage to make sure it returns a 200 HTTP status code.  The second test will make sure the `/admin` path returns 403 forbidden.

In a third test we can assert there is one logo image on the homepage, like so:

    - url: /
      find:
        - dom: '#logo img'
          count: 1

For more code examples explore the _/install_ directory.

## Terms Used

* _Test Runner_ - A very simple PHP file that defines the configuration and what test suites to run, and in what order.  @see _includes/runner.php_.
* _Test Suite_ - A YAML file that includes one or more checks against URLs. @see _includes/suite.yml_.
* _Test_ - A single URL check within a suite.
* _Assertion_ - A single find action against the response body of a test, or a validation that the HTTP response code matches an expected value.

## Install

The following creates a stand-alone project in a folder named _check-pages_.  _See also Install In Another Composer Project_.

    $ composer create-project aklump/check-pages

## Quick Start

Run the example tests with the following commands.  Then open up the files in the _tests_ directory and study them to see how they work.<sup>1</sup>

    $ cd check-pages
    $ ./check_pages runner.php

Some failing tests are also available to explore:

    $ ./check_pages failing_tests_runner.php
    
<sup>1</sup> If you see no _tests_ directory then create one and copy the contents of _install_ into _tests_.  The example _tests_ directory will only be created if you use `create-project` as the installation method.
 
### Troubleshooting

Try using the `--show-source` to see the response source code as well.
    
    ./check_pages failing_tests_runner.php --show-source  

### Quiet Mode

To make the output much simpler, use the `--quite` flag.  This will hide the assertions and reduce the output to simply pass/fail.

    ./check_pages failing_tests_runner.php --quiet

## On Your Own

When you are ready you should delete the contents of the _tests_ folder and write your own tests there.  Don't worry, the original example files are located in the _install_ directory.  (If you have used the alternate installation method you will need to write your tests in another folder of your choosing not located in this project.  But for these examples, we'll assume a `create-project` installation.)

You will need a bare minimum file structure resembling:
    
    .
    └── tests
        └── config.yml
        ├── suite.yml
        └── runner.php

### Multiple Configuration Files

The project is designed to be able to run the same tests using different configurations.  You can create multiple configuration files so that you are able to run the same test on live and then on dev, which have different base URLs. 

    .
    └── tests
        ├── config.dev.yml
        ├── config.live.yml
        ├── suite.yml
        └── runner.php
    
In _runner.php_ use the following line to specify the default config file:

    load_config('config.dev');

When you're ready to run this using the live config add the config filename to the CLI command, e.g.,

    $ ./check_pages runner.php --config=config.live

### Test functions

The test functions for your PHP test files are found in _includes/test_functions.inc_.

## Install In Another Composer Project

If you want to share dependencies with another project, like Drupal 8 for example, then use the alternative installation method.  The `--dev` flag is shown here, but use your own discretion.  Run the following from your Drupal app root directory.

    $ composer require aklump/check-pages --dev

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
        
## Javascript Is Supported

* Javascript is supported if you have Chrome installed.
* Look to _bin/chrome.sh_ as an example of how to start up Chrome.
* [Learn more](https://developers.google.com/web/updates/2017/04/headless-chrome)
* https://github.com/GoogleChrome/chrome-launcher
* https://peter.sh/experiments/chromium-command-line-switches/
* https://raw.githubusercontent.com/GoogleChrome/chrome-launcher/v0.8.0/scripts/download-chrome.sh

## Limitations

* This does not run Javascript, so DOM selections will not work if they require that JS run.
    
## Contributing

If you find this project useful... please consider [making a donation](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4E5KZHDQCEUV8&item_name=Gratitude%20for%20aklump%2Fcheck-pages).

## Testing

1. Open a new shell window which will run the PHP server for our test subject pages.
1. `./bin/server.sh`
1. Open a second shell window.
1. `./bin/test.sh`
