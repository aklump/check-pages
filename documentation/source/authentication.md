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

1. Add the following to your test runner file. Notice we include the path to the user data file. It must be a resolvable path.

    ```php
    with_extras('drupal8', [
      'users' => 'config/users.yml',
    ]); 
    ```

1. If the login form is located at a non-standard URL, you may indicate that URL, which renders the login form, as shown here.

    ```php
    with_extras('drupal8', [
      'users' => 'config/users.yml',
      'login_url' => '/login',
    ]); 
    ```

1. In your test suite add the line `user` key to each test with the value of the username to be logged in as when visiting the URL.

    ```yaml
    -
      user: admin
      visit: /admin
    -
      user: member
      visit: /admin
      expect: 403
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
