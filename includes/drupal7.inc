<?php

/**
 * @file
 * Support for Drupal 7 websites.
 *
 * Installation:
 *
 * 1. Create a json file with a list of users in the following format, do not
 * commit to source control for security sake.  You may also use YAML files, in
 * which case replace users.json with users.yaml or users.yml.
 *
 *     [{"user":"foo","pass":"bar"},{"user":"alpha","pass":"bravo"}]
 *
 * 2. Add the following to all runner files, where the path is to your JSON.
 *
 *     with_extras('drupal7', [
 *       'users' => 'config/users.json',
 *       'url' => '/user/login',
 *       'form' => '[data-drupal-selector="user-login-form"]',
 *     ]);
 *
 * 3. Include the `user` option in a test and the request will be authenticated.
 *
 *     -
 *       user: foo
 *       visit: /some/path
 */

use AKlump\CheckPages\Options\AuthenticateDrupal7;
use AKlump\LoftLib\Bash\Color;

add_test_option('user', [
  'onBeforeRequest' => function ($username, $driver, $context) use ($drupal7) {
    $sessions = $context['runner']->getStorage()->get('drupal7.sessions');
    if (empty($sessions[$username])) {
      if (!array_key_exists('users', $drupal7)) {
        throw new \InvalidArgumentException('You must provide a filepath to the users list as "users".');
      }

      echo Color::wrap('green', sprintf('👤 Creating session for %s... ', $username));

      // Load our non-version username/password index.
      $path_to_users_list = $context['runner']->resolve($drupal7['users']);
      $login_url = $drupal7['url'] ?? '/user/login';
      $absolute_login_url = $context['runner']->url($login_url);
      $auth = new AuthenticateDrupal7($path_to_users_list, $absolute_login_url);
      $auth->login($username, $auth->getUser($username)['pass']);

      echo Color::wrap('green', "logged in!\n\n");

      $sessions[$username] = $auth->getSessionCookie();
      $context['runner']->getStorage()->set('drupal7.sessions', $sessions);
    }
    $driver->setHeader('Cookie', $sessions[$username]);
  },
]);
