# Check Pages

This project means to provide a very fast-to-write means of QA testing for your website project.  You write your test suites in YAML and they look as simple as this:

    - url: /
    - url: /admin
      expect: 403

That suite will check the homepage to make sure it returns a 200 HTTP status code.  Then it will make sure the `/admin` path returns forbidden.

In the second suite we check that there is one logo image on the homepage, like so:

    - url: /
      find:
        - dom: '#logo img'
          count: 1

For other examples explore the _/install_ directory.

## Install

Run the following and a stand-alone project will be created in a folder called _check-pages_.

    $ composer create-project aklump/check-pages

## Quick Start

To see the example test suite being run do the following.  Then open up the files in the _tests_ directory and study them to see how they work.

    $ cd check-pages
    $ ./check_pages test.php

Some failing tests are also available to explore:

    $ ./check_pages failing_test.php
    
### Troubleshooting

Try using the `--debug` parameter to troubleshoot failures.  Add `--show-source` to see the response source code as well.

    ./check_pages failing_test.php --debug
    ./check_pages failing_test.php --debug --show-source  

## On Your Own

When you are ready you should delete the contents of the _tests_ folder and write your own tests.  Don't worry, the original example files are located in the _install_ directory.

You will need a bare minimum file structure resembling:
    
    .
    ├── config.yml
    ├── suite.yml
    └── test.php

### Multiple Configuration Files

The project is designed to be able to run the same tests using different configurations.  You can create multiple configuration files so that you are able to run the same test on live and then on dev, which have different base URLs. 

    .
    ├── config.dev.yml
    ├── config.live.yml
    ├── suite.yml
    └── test.php
    
In _test.php_ use the following line to specify the default config file:

    load_config('config.dev');

When you're ready to run this using the live config add the config filename to the CLI command, e.g.,

    $ ./check_pages test.php --config=config.live

### Test functions

The test functions for your PHP test files are found in _includes/test_functions.inc_.
    
## Contributing

If you find this project useful... please consider [making a donation](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4E5KZHDQCEUV8&item_name=Gratitude%20for%20aklump%2Fcheck-pages).

## Install In Another Composer Project
If you want to share dependencies with another project, like Drupal 8 for example, then run the following from your Drupal app root directory.

    $ composer require aklump/check-pages

### Usage

In this case, since the project will be buried in your vendor directory, you will need to provide the directory path to your test files, e.g.,

    ./check_pages test.php --dir=/Users/aklump/Projects/app/tests_check_pages    
## Limitations

* This does not run Javascript, so DOM selections will not work if they require that JS run.
