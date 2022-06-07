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
 *    - ${USER.id}
 *    - ${USER.uid} (same as user.id)
 *    - ${USER.mail}
 *    - ${USER.name}
 *    - ${USER.pass}
 *    - ${USER.csrf}
 *
 * For example you can write: visit: /user/{foo.uid}/edit
 */

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\DriverEventInterface;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Options\AuthenticateProviderFactory;
use AKlump\LoftLib\Bash\Color;

$_get_session = function (string $username, \AKlump\CheckPages\Parts\Test $test) use ($mixin_config) {
  $runner = $test->getRunner();

  static $sessions;
  if (is_null($sessions)) {
    $sessions = $runner->getStorage()->get('drupal.sessions');
  }

  // Check for expiry and discard if passed.
  if (empty($sessions[$username]['expires']) || $sessions[$username]['expires'] < time()) {
    unset($sessions[$username]);
  }

  if (empty($sessions[$username])) {
    $test->addBadge('🔐');
    if (!array_key_exists('users', $mixin_config)) {
      throw new \InvalidArgumentException('You must provide a filepath to the users list as "users".');
    }

    // Load our non-version username/password index.
    if (empty($mixin_config['users'])) {
      throw new \RuntimeException('Missing value for "users" in the drupal mixin.');
    }
    $path_to_users_list = $runner->resolveFile($mixin_config['users']);
    $login_url = $mixin_config['login_url'] ?? '/user/login';
    $absolute_login_url = $runner->url($login_url);

    $factory = new AuthenticateProviderFactory();
    $auth = $factory->get($path_to_users_list, $absolute_login_url);

    $user = $auth->getUser($username);
    $auth->login($user);
    $account = $user->jsonSerialize();
    if (empty($account['uid'])) {
      throw new \RuntimeException(sprintf('Could not determine user ID for %s', $username));
    }

    $sessions[$username] = [
      'cookie' => $auth->getSessionCookie(),
      'expires' => $auth->getSessionExpires(),
      'account' => $account,
      'csrf_token' => $auth->getCsrfToken(),
    ];
    $runner->getStorage()->set('drupal.sessions', $sessions);
  }

  return $sessions[$username];
};

add_test_option('user', [
  'onBeforeTest' => function ($username, TestEventInterface $event) use ($_get_session) {
    if (empty($username)) {
      return;
    }

    // Add in user-based variables for later interpolation.
    $test = $event->getTest();
    $session = $_get_session($username, $test);
    $test->variables()
      ->setItem('user.id', $session['account']['uid'])
      ->setItem('user.uid', $session['account']['uid'])
      ->setItem('user.mail', $session['account']['mail'])
      ->setItem('user.name', $session['account']['name'])
      ->setItem('user.pass', $session['account']['pass'])
      ->setItem('user.csrf', $session['csrf_token']);
  },

  'onBeforeRequest' => function ($username, DriverEventInterface $event) use ($_get_session) {
    if (empty($username)) {
      return;
    }

    // Add visual feedback that the request is authenticated.
    $output = $event->getTest()->getRunner()->getOutput();
    if ($output->isVeryVerbose()) {
      $event->getTest()
        ->addBadge(sprintf('👤%s ', Color::wrap('light gray', $username)));
    }
    else {
      $event->getTest()->addBadge('👤');
    }

    // Add the session cookie header to requests to make them authenticated.
    $session = $_get_session($username, $event->getTest());
    $event->getDriver()->setHeader('Cookie', $session['cookie']);
  },

]);

/**
 * Delete any sessions that were captured in Storage.  This is more reliable
 * than keeping the sessions across runner executions and depending on the
 * session expiry, which may lead to sessions that no longer exist on the
 * remote, and subsequently annoying test failures.  The performance hit of this
 * has been measured and is very small.  The reliability gain far exceeds the
 * slight hit in performance.
 */
respond_to(Event::RUNNER_FINISHED, function () use ($runner) {
  $runner->getStorage()->set('drupal.sessions', []);
});
