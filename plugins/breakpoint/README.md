# Breakpoint

Provides a means of stopping a suite until a key is pressed. Use this like you would a debugging breakpoint when writing tests if you need to examine server state, for example.

Any key will go on to the next test, however CTRL-C will exit the suite as you might expect.

**To enable<sup>1</sup> the breakpoints you must use the `debug` runner option, e.g.,**

`./vendor/bin/check_pages run tests/runner.php --debug=1`

<sup>1</sup>They are disabled when you omit the option, or use `--debug` or `--debug=0`.
