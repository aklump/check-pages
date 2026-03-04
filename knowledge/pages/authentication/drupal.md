<!--
id: authentication
tags: ''
-->

# Authentication

If you want to check pages as an authenticated user of a website, then you have to be logged in, a.k.a., _authenticated_, before the request is made. Some authentication providers are built-in, or you can write your own using  [add_mixin()](@options). You might refer to _includes/drupal8.inc_ as a starting point.

## Basic Setup

This example will use the `drupal8` built-in authentication provider. _Note: for Drupal 7 authentication, simply change `add_mixin('drupal'...` to `add_mixin('drupal7'...`_.

1. Setup the user files as explained in [Users](@users)

2. Add the following to your test runner file. This must come AFTER `load_config`. This tells your _runner.php_ to include the Drupal 8 authentication and from where to pull the user data.

    ```php
    # File: runner.php
    add_mixin('drupal', [
      'users' => config_get('extras.users')
    ]); 
    ```


## Variations on the Above

1. If the login form is located at a non-standard URL, you may indicate that URL, which renders the login form, as shown here.

    ```php
    add_mixin('drupal', [
      'users' => config_get('extras.users'),
      'login_url' => '/login',
    ]); 
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
