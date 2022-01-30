<?php

/**
 * @file
 * Support for Drupal 7 websites.
 *
 * Installation:
 *
 * 1. Create a json (or YAML) file with a list of users in the following format, do not
 * commit to source control for security sake.  You may also use YAML files, in
 * which case replace users.json with users.yaml or users.yml.
 *
 *     [{"user":"foo","pass":"bar"},{"user":"alpha","pass":"bravo"}]
 *
 * 2. Add the following to all runner files, where the path is to your JSON.
 *
 *     with_extras('drupal7', [
 *       'users' => 'config/users.json',
 *       'login_url' => '/user/login',
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
    $runner = $context['runner'];
    $sessions = $runner->getStorage()->get('drupal7.sessions');
    if (empty($sessions[$username])) {
      if (!array_key_exists('users', $drupal7)) {
        throw new \InvalidArgumentException('You must provide a filepath to the users list as "users".');
      }

      echo "🔐";

      // Load our non-version username/password index.
      $path_to_users_list = $runner->resolve($drupal7['users']);
      $login_url = $drupal7['login_url'] ?? '/user/login';
      $absolute_login_url = $runner->url($login_url);
      $auth = new AuthenticateDrupal7($path_to_users_list, $absolute_login_url);
      $auth->login($username, $auth->getUser($username)['pass']);

      $sessions[$username] = $auth->getSessionCookie();
      $runner->getStorage()->set('drupal7.sessions', $sessions);
    }

    if ($runner->getOutputMode() !== \AKlump\CheckPages\Parts\Runner::OUTPUT_QUIET) {
      echo Color::wrap('green', sprintf('(👤 %s) ', $username));
    }
    else {
      echo '👤 ';
    }
    $driver->setHeader('Cookie', $sessions[$username]);
  },
]);
