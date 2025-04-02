## Critical
  
- need to be able to set value on bash output

```yaml
  bash: cd /Users/aaronklump/Code/Projects/NationalUniversity/TheCommons/site/app && lando nxdb_drush uinf ${loadUserName} --field=uid
  set: userId
```

- need to throw error when using `attr` not `attribute`
- promote env_vars to a handler?
- add test coverage for env_vars mixin
- badge icons may be broken... see sessions.php

---

- Add unit test for \AKlump\CheckPages\Mixins\Drupal\DrupalSessionManager
- rewrite homepage with Composer install
- finish docs, especially the hooks
- dynamically add the `/extra/merge-plugin/require` section for handlers so they are auto added.
- in TestRunner on line 196 ( $assert = $assert_runner->run(new Assert($id, $definition, $test));)) if JsonSchema.php tries to include a class not exists, there is no error output.

## Normal

- a means of setting a bandwidth throttle
- look into upgrading per https://github.com/aklump/check-pages/security/dependabot
- "spatie/browsershot": "^3.0 || ^4.0 || ^5.0", ... this will require >= php 8.2
- normalize handler classes to all be inside src for easier clarity and autoloading, update auto load docs? Not sure because this make adding to the phunit coverage a bit more work
- when using drupal mixin, it makes the get request to the form id, even if `user` key is not in the test, it should skip.
- replace DotArray::create in Path handler for PHP 8.1 support.
- 'add CP eval support for : 2024-02-22 < 2024-02-24'
- move AddHandlerAutoloads to a cached solution.
- some passwords do not work in drupal, e.g. 'EGucqaBbgfajFVpkLnh4TP4ur4EoPrqjDKmUL8wegGQUyA4jcEawhEQhrjfAWdFb@vfi7ihr!cZBh3qViYqEP@Y4dFBB2X_hXx-Y' must have to do with special chars that need to be escaped.

### Form Handler

- document this (fix knowledge?)
- prevent this from happening: `1[name]=pass&1[value]=pass&op=Next&input=uber&form_build_id=form-EYcmhwbcrV34PSUt8ecSDNw21SadTTljqcmswa2Cf2M&form_id=user_login_form&pass=pass`

## Complete
