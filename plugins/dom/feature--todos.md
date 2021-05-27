<feature>
---
# Todo

## Plugin

1. Start writing _suite.yml_ and add to _test_subject.html_ as necessary. You may replace _test_subject.html_ with _test_subject.php_ if necessary.
1. Make sure you include all (uncovered by other tests and logical) permutations in _suite.yml_.
1. Now work on _Dom.php_.

## Running Plugin Tests

1. Be sure to run `bump build` when you are done with your plugin to compile the plugin into the app. There may be a PhpStorm file watcher doing this automatically.
1. Run tests for a single plugin with `./check_pages ./example/tests/runner_plugins.php --filter=dom`

