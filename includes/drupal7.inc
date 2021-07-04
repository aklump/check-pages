<?php
/**
 * @file
 * Support for Drupal 7 websites.
 *
 * Installation:
 *
 * 1. Create a json file with a list of users in the following format, do not
 * commit to source control for security sake.
 *
 *     [{"username":"foo","password":"bar"}]
 *
 * 2. Add the following to all runner files, where the path is to your JSON.
 *
 *     with_extras('drupal7', [
 *       'users_json' => __DIR__ . '/config/users.json',
 *     ]);
 *
 * 3. Include the `user` option in a test and the request will be authenticated.
 *
 *     -
 *       user: foo
 *       visit: /some/path
 */

add_test_option('user', [
  'onBeforeRequest' => function ($username, $driver) use ($drupal7) {
    static $cookies, $users;
    if (empty($users)) {

      // Load our non-version username/password index.
      $users = json_decode(file_get_contents($drupal7['users_json']), TRUE);
    }

    // Find the account by username.
    $user = array_first(array_filter($users, function ($account) use ($username) {
      return $account['username'] === $username;
    }));
    $password = $user['password'];
    $cid = "$username:$password";

    // Log in and get a session cookie for this user.
    if (empty($cookies[$cid])) {

      // TODO Log in and obtain the session cookie with $username/$password.

      $cookies[$cid] = 'SSESS95e5b040bf9ce8d9832cea8743568058=XLH19XjcGyMZn81g0f4YrN6DWpJK4vFMAH8asIRxAT4';
    }
    $driver->addHeader('Cookie', $cookies[$cid]);
  },
]);
