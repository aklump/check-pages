<?php

/**
 * @file
 * Support for Drupal 8 websites.
 *
 * Installation:
 *
 * 1. Create a json (or YAML) file with a list of users in the following format, do not
 * commit to source control for security sake.  You may also use YAML files, in
 * which case replace users.json with users.yaml or users.yml.
 *
 *     [{"name":"foo","pass":"bar"},{"name":"alpha","pass":"bravo"}]
 *
 * 2. Add the following to all runner files, where the path is to your JSON.
 *
 *     add_mixin('drupal', [
 *       'users' => 'config/users.json',
 *       'login_url' => '/user/login',
 *     ]);
 *
 * 3. Include the `user` option in a test and the request will be authenticated.
 *
 *     -
 *       user: foo
 *       visit: /some/path
 *
 * 4. This will also provide the following replacement variables which you can
 * use to write dynamic suites and tests:
 *
 *    - ${user.id}
 *    - ${user.uid} (same as user.id)
 *    - ${user.name}
 *    - ${user.mail}
 *    - ${user.pass}
 *
 * For example you can write: visit: /user/{user.uid}/edit
 */

use AKlump\CheckPages\Options\AuthenticateProviderFactory;
use AKlump\CheckPages\Parts\Runner;
use AKlump\LoftLib\Bash\Color;
use AKlump\CheckPages\Parts\Test;

$_get_session = function (string $username, Runner $runner) use ($config) {
  $sessions = $runner->getStorage()->get('drupal.sessions');

  // Check for expiry and discard if passed.
  if (empty($sessions[$username]['expires']) || $sessions[$username]['expires'] < time()) {
    unset($sessions[$username]);
  }

  if (empty($sessions[$username])) {
    if (!array_key_exists('users', $config)) {
      throw new \InvalidArgumentException('You must provide a filepath to the users list as "users".');
    }
    echo "🔐";

    // Load our non-version username/password index.
    $path_to_users_list = $runner->resolveFile($config['users']);
    $login_url = $config['login_url'] ?? '/user/login';
    $absolute_login_url = $runner->url($login_url);

    $factory = new AuthenticateProviderFactory();
    $auth = $factory->get($path_to_users_list, $absolute_login_url);

    $user = $auth->getUser($username);
    $auth->login($user);

    $sessions[$username] = [
      'cookie' => $auth->getSessionCookie(),
      'expires' => $auth->getSessionExpires(),
      'account' => $user->jsonSerialize(),
      'csrf_token' => $auth->getCsrfToken(),
    ];
    $runner->getStorage()->set('drupal.sessions', $sessions);
  }

  return $sessions[$username];
};

add_test_option('user', [

  'onBeforeTest' => function ($username, Test $test, $context) use ($_get_session) {
    $session = $_get_session($username, $context['runner']);
    $context['runner']->getSuite()
      ->variables()
      ->setItem('user.id', $session['account']['uid'])
      ->setItem('user.uid', $session['account']['uid'])
      ->setItem('user.name', $session['account']['name'])
      ->setItem('user.pass', $session['account']['pass'])
      ->setItem('user.csrf', $session['csrf_token']);
  },

  'onBeforeRequest' => function ($username, $driver, $context) use ($_get_session) {
    $runner = $context['runner'];
    if ($runner->getOutputMode() !== Runner::OUTPUT_QUIET) {
      echo Color::wrap('green', sprintf('(👤 %s) ', $username));
    }
    $session = $_get_session($username, $runner);
    $driver->setHeader('Cookie', $session['cookie']);
  },
]);
