# Continuing and Retesting

**These features only work when file storage is enabled.**

## The Continue Option

If you have lots of suites, and you happen to interrupt the runner after you've completed a portion, you may want to continue, rather than start over. This sets up the scenario where you will use the `--continue` option.

It works by skipping all the suites before the one that was interrupted, and beings with that suite's first test. It means some of the tests in the suite will be repeated, that is, it doesn't continue with the last test, but the last suite.

## The Retest Option

If you have just run a bunch of suites, and had only a few failures, which you think you've fixed, you will want to use the `--retest` option.

This works by re-running any suite, which had one or more test failures. Yes, the entire suite is retested, not just the failing test in the suite.

