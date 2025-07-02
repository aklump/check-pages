## Critical

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

- form test is failing

### Form Handler

- document this (fix knowledge?)
- prevent this from happening: `1[name]=pass&1[value]=pass&op=Next&input=uber&form_build_id=form-EYcmhwbcrV34PSUt8ecSDNw21SadTTljqcmswa2Cf2M&form_id=user_login_form&pass=pass`

Interpolation fails for `text`, e.g. count is interpolated as null for some reason.  I think it's because the test interpolates BEFORE the suite interpolates on the find array.

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
- test scope and suite scope for interpolation is confusing and unnecessary, they should become a single scope.

- we sometimes get a curl error, then immediately it works with --retest.  could this be "fixed" by using a while() loop to auto retry on certain curl errors?  I'm thinking it's resource is just crashing.
- a means of setting a bandwidth throttle
- look into upgrading per https://github.com/aklump/check-pages/security/dependabot
- "spatie/browsershot": "^3.0 || ^4.0 || ^5.0", ... this will require >= php 8.2
- normalize handler classes to all be inside src for easier clarity and autoloading, update auto load docs? Not sure because this make adding to the phunit coverage a bit more work
- when using drupal mixin, it makes the get request to the form id, even if `user` key is not in the test, it should skip.
- replace DotArray::create in Path handler for PHP 8.1 support.
- 'add CP eval support for : 2024-02-22 < 2024-02-24'
- move AddHandlerAutoloads to a cached solution.
- some passwords do not work in drupal, e.g. 'EGucqaBbgfajFVpkLnh4TP4ur4EoPrqjDKmUL8wegGQUyA4jcEawhEQhrjfAWdFb@vfi7ihr!cZBh3qViYqEP@Y4dFBB2X_hXx-Y' must have to do with special chars that need to be escaped.


## Complete
