## Critical

- form handler: prevent this from happening: `1[name]=pass&1[value]=pass&op=Next&input=uber&form_build_id=form-EYcmhwbcrV34PSUt8ecSDNw21SadTTljqcmswa2Cf2M&form_id=user_login_form&pass=pass`

- commit tests_phpunit/files/*
- --continue needs to go back one test, I think it's starting late. WE MUST HAVE A TEST TO ENSURE THIS DOESN'T REGRESS
- TEST FOR --retest PICKS UP AT THE CORRECT PLACE.
- files are not getting closed during test runs; troubleshoot and fix. ulimit ISSUE

* https://medium.com/mindful-technology/too-many-open-files-limit-ulimit-on-mac-os-x-add0f1bfddde

## Me

how to fix Too many open files in /Users/aklump/Code/Packages/cli/check-pages/app/vendor/composer/ClassLoader.php on line 576

* https://packagist.org/packages/cronfig/sysinfo

## AI Assistant

It's not caused by a bug in the code you've shown but rather by the way the code is being used or the system's configuration. Here's how to troubleshoot and fix it:

1. **Check System Limits:** Use the `ulimit -n` command in your terminal to see the current limit. If it's too low, increase it. You'll need administrator privileges to do this. A common way to increase the limit is to add `ulimit -n 65536` to your shell's configuration file (e.g., `.bashrc`, `.zshrc`).

2. **Close Unused Files:**  Ensure your application closes files properly after using them. Look for places where files are opened but not explicitly closed, especially in loops or repeated operations. Use `fclose()` in PHP to close file handles. For network connections or sockets, ensure they're closed properly.

3. **Check for File Descriptor Leaks:** If files are being opened but not closed due to a bug or unexpected behavior in your application, this can lead to leaks. Carefully review your code for proper file handling, particularly in error handling and cleanup routines.

If you're still having trouble after trying these steps, please provide more information about how the `HttpClient` class is being used in your application. For example, are you making a large number of requests concurrently, or are you reusing the `HttpClient` instance for multiple requests? This information will help in providing more specific guidance.

## Me

how to I runtime set that?

## AI Assistant

You can't directly change the `ulimit` setting from within a PHP script reliably due to security restrictions and how PHP processes are handled. The `ulimit` command affects the shell environment, and a PHP script typically runs as a child process, inheriting limits but unable to change them for the parent or system-wide. Changes made within the script would only affect that specific script's execution and not other processes or subsequent runs.

The most reliable way to adjust the open file limit is by modifying the shell startup files as described in my previous response, or using system administration tools provided by your operating system. Those methods ensure the limit is set correctly before the PHP process even starts.

---

- assert \AKlump\CheckPages\Parts\Runner::executeRunner passes only these defined vars: $path
- tests for all runner_functions

---

- assert that StopRunnerException shows without verbose
- assert that StopRunnerException shows in -v
- [x] assert that StopRunnerException shows in -vv
- [x] assert that StopRunnerException shows in -vvv

.foobar__action a*3

not works

```yaml
    -
      why: Assert member can leave the group.
      dom: .foobar__action a
      href: /group/68282/leave
      count: 1
```

works.

```yaml
    -
      why: Assert member can leave the group.
      dom: .foobar__action a[href="/group/68282/leave"]
      count: 1
```

Interpolation fails for `text`, e.g. count is interpolated as null for some reason. I think it's because the test interpolates BEFORE the suite interpolates on the find array.

```yaml
-
  set: count
  value: 161507
-
  url: /widget
  find:
    -
      dom: '#updates a>strong'
      text: ${count}
```

The following fails when looking for the content in AJAX loaded content.

```yaml
find:
  - lorem ipsum
```

- rewrite \AKlump\CheckPages\Exceptions\TestFailedException::__construct() to take the test not the config, or the messages. Anyway we need to be able to extract the test messages from the exception, e.g. getTestMessages(), so they will be displayed and moved to the runner. Need to add test coverage to prevent regression.
- display path to the output files in verbose mode

- need to be able to set value on bash output

```yaml
  bash: cd /Users/aaronklump/Code/Projects/NationalUniversity/TheCommons/site/app && lando nxdb_drush uinf ${loadUserName} --field=uid
  set: userId
```

- need to throw error when using `attr` not `attribute`
- promote env_vars to a handler?
- add test coverage for env_vars mixin
- badge icons may be broken... see sessions.php
- Add unit test for \AKlump\CheckPages\Mixins\Drupal\DrupalSessionManager

---

- rewrite homepage with Composer install
- finish docs, especially the hooks
- dynamically add the `/extra/merge-plugin/require` section for handlers so they are auto added.
- in TestRunner on line 196 ( $assert = $assert_runner->run(new Assert($id, $definition, $test));)) if JsonSchema.php tries to include a class not exists, there is no error output.

## Normal

- get rid of all global vars
- wrap the entry file in IIFE pattern to isolate variables.

- add symfony output progress bars for better DX?
- can we simplify Variables class by leveraging https://github.com/aklump/token-engine
- test scope and suite scope for interpolation is confusing and unnecessary, they should become a single scope.

- we sometimes get a curl error, then immediately it works with --retest. could this be "fixed" by using a while() loop to auto retry on certain curl errors? I'm thinking it's resource is just crashing.
- a means of setting a bandwidth throttle
- look into upgrading per https://github.com/aklump/check-pages/security/dependabot
- "spatie/browsershot": "^3.0 || ^4.0 || ^5.0", ... this will require >= php 8.2
- normalize handler classes to all be inside src for easier clarity and autoloading, update auto load docs? Not sure because this make adding to the phunit coverage a bit more work
- when using drupal mixin, it makes the get request to the form id, even if `user` key is not in the test, it should skip.
- 'add CP eval support for : 2024-02-22 < 2024-02-24'
- move AddHandlerAutoloads to a cached solution.
- some passwords do not work in drupal, e.g. 'EGucqaBbgfajFVpkLnh4TP4ur4EoPrqjDKmUL8wegGQUyA4jcEawhEQhrjfAWdFb@vfi7ihr!cZBh3qViYqEP@Y4dFBB2X_hXx-Y' must have to do with special chars that need to be escaped.

## Complete
