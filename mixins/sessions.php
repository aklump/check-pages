<?php

namespace AKlump\CheckPages\Mixins\Session;

use AKlump\CheckPages\Browser\Session;
use AKlump\CheckPages\DataStructure\User;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\DriverEventInterface;
use AKlump\CheckPages\Exceptions\StopRunnerException;
use AKlump\CheckPages\Files\LoadUsers;
use AKlump\CheckPages\Frameworks\Drupal\ValidateSession;
use AKlump\CheckPages\Helpers\FilterUsersByName;
use AKlump\LoftLib\Bash\Color;

/**
 * Provide Check Pages with a Mixin Called My Mixin
 *
 * 1. Create config/users.yml or config/users.json.
 * 1. Add at least one user record as shown below.
 * 1. Register this mixin in your runner file.
 * 1. Add the `user` key to any test and the session cookie will be sent in
 * the request for that test.s
 *
 * Usage in config/users.yml:
 *
 * @code
 * -
 *   user: foobar
 *   session: SSESSf99b4ea981b1850593a7c4b0249ea3a2=0YxPBfvlZGApBk6d9YBdMdvXs0Yjg5WaFBmFMMZ4VEY4ponu
 * @endcode
 *
 * Usage in runner.php:
 *
 * @code
 * add_mixin('mixins/session', [
 *   'users' => 'config/users.yml',
 * ]);
 * @endcode
 *
 * Usage in your test file:
 *
 * @code
 * -
 *   visit: /
 *   user: foobar
 * @endcode
 */

/** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
/** @var \AKlump\CheckPages\Parts\Runner $runner */
/** @var array $mixin_config */

$files = $runner->getFiles();
$credentials_file = $mixin_config['users'] ?? '';
$credentials_file = $files->tryResolveFile($credentials_file)[0] ?? '';
$users = (new LoadUsers())($credentials_file);

add_test_option('user', [
  Event::REQUEST_CREATED => function ($username, DriverEventInterface $event) use ($users) {
    if (empty($username)) {
      return;
    }

    //
    // Locate the session by username
    //
    $session = new Session();
    $user = (new FilterUsersByName($users))($username);
    if ($user) {
      $session->setUser($user);
      $session_data = $user->getProperty('session');
      $session->setName($session_data['name'] ?? '');
      $session->setValue($session_data['value'] ?? '');
    }

    //
    // Set the session cookie header
    //
    $cookie = $session->getSessionCookie();
    if (!$cookie) {
      throw new StopRunnerException("No session found for user: $username. Try: export CP_USERNAME=foobar");
    }
    $event->getDriver()->setHeader('Cookie', $cookie);

    $validated_user = validateSession($session, $event->getTest()
      ->getRunner()
      ->getBaseUrl());
    if (!$validated_user->getAccountName()) {
      $validated_user->setAccountName($username);
    }

    // TODO These are not being set correctly I don't think.
    $variables = $event->getTest()->getSuite()->variables();
    $variables->setItem('user.id', $validated_user->id())
      ->setItem('user.uid', $validated_user->id())
      ->setItem('user.mail', $validated_user->getEmail())
      ->setItem('user.name', $validated_user->getAccountName())
      ->setItem('user.pass', $validated_user->getPassword())
      ->setItem('user.timezone', $validated_user->getTimeZone()->getName());

    //
    // Add visual feedback that the request is authenticated.
    //
    $output = $event->getTest()->getRunner()->getOutput();
    if ($output->isVeryVerbose()) {
      $event->getTest()
        ->addBadge(sprintf('👤%s ', Color::wrap('light gray', $session->getUser()
          ->getAccountName())));
    }
    else {
      $event->getTest()->addBadge('👤');
    }
  },
]);


/**
 * @throws \AKlump\CheckPages\Exceptions\StopRunnerException
 */
function validateSession($session, string $base_url): User {
  // TODO Add caching so this doesn't validate every test
  $user = (new ValidateSession($base_url))($session);
  if (!$user) {
    throw new StopRunnerException(sprintf('Session is invalid or expired for user: %s.', $session->getUser()
      ->getAccountName()));
  }

  return $user;
}
