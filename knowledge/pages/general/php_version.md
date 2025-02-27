<!--
id: php_version
tags: ''
-->

# How to Use a Different PHP version than Default

If you've installed Check Pages globally, you may find that you need to specify the exact PHP version that runs it, so that the dependencies that were installed are correct.

This will use the current PHP version of the shell:

```shell
$(which checkpages) run tests_check_pages/test_content/setup_runner.php
```

This will use a specific PHP version, i.e. `$CHECK_PAGES_PHP`:

```shell
export CHECK_PAGES_PHP="/Applications/MAMP/bin/php/php7.4.33/bin/php"
"$CHECK_PAGES_PHP" $(which checkpages) run tests_check_pages/test_content/setup_runner.php
```
