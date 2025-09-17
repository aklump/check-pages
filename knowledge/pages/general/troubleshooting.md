<!--
id: troubleshooting
tags: ''
-->

# Troubleshooting

If you are using multiple instances of PHP on your system, or accross different projects this presents an additional challenge.  **You must ensure that you run _Check Pages_ using the same PHP version with which it was installed.**

## Composer Issues When Running Tests

If  `./bin/run_check_pages_tests.sh` tells you _Composer detected issues in your platform_ then you probably need to hardcode the PHP version into that file. Open the file and look for `CHECK_PAGES_PHP` and set its value to match the PHP used to install _Check Pages_.

## Setup an Alias for Alternative PHP

Add something like the following to your terminal config, adjusting for the correct PHP path.

```
alias checkpages="/Applications/MAMP/bin/php/php8.1.31/bin/php $(which checkpages)"
```
