# Check Pages

This project intends to provide a process of QA testing of a website, which is very fast to implement and simple to maintain.  You write your test suites in YAML and they can look as simple as this:

    - url: /
    - url: /admin
      expect: 403

That suite will check the homepage to make sure it returns a 200 HTTP status code.  Then it will make sure the `/admin` path returns forbidden.

In another suite we check that there is one logo image on the homepage, like so:

    - url: /
      find:
        - dom: '#logo img'
          count: 1

For more code examples explore the _/install_ directory.

## Install

The following creates a stand-alone project in a folder named _check-pages_.  _See also Install In Another Composer Project_.

    $ composer create-project aklump/check-pages

## Quick Start

Run the example test suite<sup>1</sup> with the following commands.  Then open up the files in the _tests_ directory and study them to see how they work.

    $ cd check-pages
    $ ./check_pages test.php

Some failing tests are also available to explore:

    $ ./check_pages failing_test.php
    
<sup>1</sup> If you see no _tests_ directory then create one and copy the contents of _install_ into _tests_.  The example _tests_ directory will only be created if you use `create-project` as the installation method.
 
### Troubleshooting

Try using the `--debug` parameter to troubleshoot failures.  Add `--show-source` to see the response source code as well.

    ./check_pages failing_test.php --debug
    ./check_pages failing_test.php --debug --show-source  

## On Your Own

When you are ready you should delete the contents of the _tests_ folder and write your own tests there.  Don't worry, the original example files are located in the _install_ directory.  (If you have used the alternate installation method you will need to write your tests in another folder of your choosing not located in this project.  But for these examples, we'll assume a `create-project` installation.)

You will need a bare minimum file structure resembling:
    
    .
    └── tests
        └── config.yml
        ├── suite.yml
        └── test.php

### Multiple Configuration Files

The project is designed to be able to run the same tests using different configurations.  You can create multiple configuration files so that you are able to run the same test on live and then on dev, which have different base URLs. 

    .
    └── tests
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

## Install In Another Composer Project

If you want to share dependencies with another project, like Drupal 8 for example, then use the alternative installation method.  The `--dev` flag is shown here, but use your own discretion.  Run the following from your Drupal app root directory.

    $ composer require aklump/check-pages --dev

### Usage

In this case, since the project will be buried in your vendor directory, you will need to provide the directory path to your test files, when you run the test script, like this:

    ./vendor/bin/check_pages test.php --dir=./tests_check_pages
    
This example assumes a file structure like this:

    .
    ├── tests_check_pages
    │   └── test.php
    └── vendor
        └── bin
            └── check_pages  
        
## Limitations

* This does not run Javascript, so DOM selections will not work if they require that JS run.
    
## Contributing

If you find this project useful... please consider [making a donation](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4E5KZHDQCEUV8&item_name=Gratitude%20for%20aklump%2Fcheck-pages).
