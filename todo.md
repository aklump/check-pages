## Critical

- look into upgrading per https://github.com/aklump/check-pages/security/dependabot
- rewrite homepage with Composer install
- finish docs, especially the hooks
- dynamically add the `/extra/merge-plugin/require` section for handlers so they are auto added.
- in TestRunner on line 196 ( $assert = $assert_runner->run(new Assert($id, $definition, $test));)) if JsonSchema.php tries to include a class not exists, there is no error output.

## Normal

- 'add CP eval support for : 2024-02-22 < 2024-02-24'
- move AddHandlerAutoloads to a cached solution.
- some passwords do not work in drupal, e.g. 'EGucqaBbgfajFVpkLnh4TP4ur4EoPrqjDKmUL8wegGQUyA4jcEawhEQhrjfAWdFb@vfi7ihr!cZBh3qViYqEP@Y4dFBB2X_hXx-Y' must have to do with special chars that need to be escaped.

## Complete
