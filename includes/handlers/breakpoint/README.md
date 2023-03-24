# Breakpoint

Provides a means of stopping a suite until a key is pressed. Use this like you would a debugging breakpoint when writing tests if you need to examine server state, for example.

Any key will go on to the next test, however CTRL-C will exit the suite as you might expect.

**Breakpoints are only active in debug mode (-vvv), e.g.,**

`./vendor/bin/check_pages run tests/runner.php -vvv`
