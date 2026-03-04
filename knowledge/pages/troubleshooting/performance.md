# Performance

> Turn on Xdebug for faster performance.

For some unknown reason, turning on Xdebug is making my tests run faster, whereas disabled they crawl.  I have yet to discover why.

## Long Delay Before First Suite

This happens because...

* \AKlump\CheckPages\Parts\Runner::validateSuite hangs
