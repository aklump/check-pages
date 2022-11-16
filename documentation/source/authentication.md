# Authentication

If you want to check pages as an authenticated user of a website, then you have to be logged in, or authenticated before the request is made. Some authentication providers are built-in, or you can write your own using  [add_test_option()](@options). You might refer to _includes/drupal8.inc_ as a starting point.

## Basic Setup

This example will use the `drupal8` built-in authentication provider. _Note: for Drupal 7 authentication, simply change `add_mixin('drupal8'...` to `add_mixin('drupal7'...`_.

1. Create a YAML (or JSON) file containing user login credentials.  **Do not commit this to source control.** You can place this whereever, but in this example it will be located in the config directory as _config/users.yml_. You may list as many users as you want. Each record must have the keys `name` and `pass`. Yes, `pass` is the un-hashed plaintext password for the user, so be cautious.

    ```yaml
    # File: config/users.yml
    -
      name: admin
      pass: 123pass
    -
      name: member
      pass: secret5
    ```

2. Add the following to your test runner file. This tells your _runner.php_ to include the Drupal 8 authentication and from where to pull the user data.

    ```php
    # File: runner.php
    add_mixin('drupal8', [
      'users' => 'config/users.yml',
    ]); 
    ```

4. In your test suite add the `user` option to a test, giving the username as the value. The request for that test will be made after first authenticating that user.

   ```yaml
   # File: suite.yml
   -
     user: admin
     visit: /admin
   -
     user: member
     visit: /admin
     expect: 403

   ```

## Variations on the Above

1. If the login form is located at a non-standard URL, you may indicate that URL, which renders the login form, as shown here.

    ```php
    add_mixin('drupal8', [
      'users' => 'config/users.yml',
      'login_url' => '/login',
    ]); 
    ```

2. It's also worth nothing, once a user is authenticated, certain variables may be used on subsequenst tests. Notice how the variables persist into the second test even though it is not authenticated. The user variables will carry over into subsequent tests until the next authentication, when they will be re-set.

   ```yaml
   -
   user: testbot.member
   visit: /user/${user.uid}/edit
   -
   visit: /user/${user.uid}/edit
   expect: 403
   -
   visit: /user/${user.name}
   expect: 200
   ```
3. It's possible you don't want to use the same _users.yml_ data file for all configurations. To accommodate this you may replace the hardcoded path `config/users.yml` with `config_get('extras.users')` and add the hard-coded path to each of your configuration files.

   ```php
   # file: runner.php
   add_mixin('drupal8', [
     'users' => config_get('extras.users'),
   ]); 
   ```

   ```yaml
   # file: config/live.yml
   ...
   extras:
     users: config/users--live.yml
   ```

   ```yaml
   # file: config/dev.yml
   ...
   extras:
     users: config/users--dev.yml
   ```
4. You can capture several user IDs at once like this. Furthermore, this can be moved to an import, and you have all user IDs available to all suites, very easily by importing with `- import: imports/get_user_ids`  See [imports](@plugin_import) for more info.

   ```yaml
   # file: imports/_get_user_ids
   
   -
     user: site_test.worker
     set: workerUid
     is: ${user.id}
   -
     user: site_test.admin
     set: adminUid
     is: ${user.id}
   ```

## CSRF Tokens

The token `${user.csrf}` is created automatically when you use `user`, and can be used as shown below:

```yaml
-
  user: site_test.admin
  url: /cp-api/${cp_api_public}/jobs
  request:
    method: POST
    headers:
      X-Csrf-token: ${user.csrf}
```
