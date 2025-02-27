<!--
id: setup
tags: ''
-->

# Setup Before Test

Some integration testing requires that an environment exist in a given state before the tests can run. How can you ensure that the website is in that state before your test suite begins?

## Server-Side Setup

In this strategy, you setup an endpoint that receives an ID representing a state. You then write server-side code to put your website into that state when it receives a request. How you write the server-side is up to you, and depends on the language, framework, etc. All you do on the Check Pages side of things is make a request to that appropriate endpoint at the beginning of your suite:

```yaml
# file: suite.yml
-
  visit: /test-set-state.php?state=foo
  ...

```
