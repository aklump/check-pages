# Authentication

If you want to check pages as an authenticated user of a website, then you have to provide a login option to your test suites.

## Drupal 8

1. Create a YAML or JSON file containing user login credentials. Do not commit this to source control. You can place this whereever, but in this example it will be located in the config directory as _config/users.yml_. You may list as many users as you want. Each record must have the keys `name` and `pass`.

   ```yaml
   -
     name: admin
     pass: 123pass
   -
     name: member
     pass: secret5
   ```

2. Add the following to your test runner file. Notice we include the path to the user data file. It must be a resolvable path.

    ```php
    with_extras('drupal8', [
      'users' => 'config/users.yml',
    ]); 
    ```

3. If the login form is located at a non-standard URL, you may indicate that URL, which renders the login form, as shown here.

    ```php
    with_extras('drupal8', [
      'users' => 'config/users.yml',
      'login_url' => '/login',
    ]); 
    ```

4. In your test suite add the line `user` key to each test with the value of the username to be logged in as when visiting the URL.

    ```yaml
    -
      user: admin
      visit: /admin
    -
      user: member
      visit: /admin
      expect: 403
   ```
5. You can also use dynamic values from the authenticated user as shown below. Notice how the variables persist into the second test even though it is not authenticated. The user variables will carry over into subsequent tests until the next authentication, when they will be re-set.

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

## Advanced Configuration

You may move the `with_extras` configuration into your configuration files when necessary. Do something like the following.

```php
with_extras('drupal8', [
  'users' => config_get('extras.users'),
]); 
```

_config/live.yml_

```yaml
extras:
  users: config/users--live.yml
```

_config/dev.yml_

```yaml
extras:
  users: config/users--dev.yml
```

## Drupal 7

1. Follow instructions for Drupal 8, but change the first argument to `with_extras()` to `drupal7`, e.g.,

    ```php
    with_extras('drupal7', [
      'users' => 'config/users.yml',
    ]); 
    ```

## Custom Authentication

You can build your own authentication using the `add_test_option()` function. Refer to _drupal8.inc_ as a starting point.
