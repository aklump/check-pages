<?php

namespace AKlump\CheckPages\Mixins\Session;

use AKlump\CheckPages\Browser\Session;
use AKlump\CheckPages\DataStructure\User;
use AKlump\CheckPages\DataStructure\UserInterface;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\DriverEventInterface;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Exceptions\StopRunnerException;
use AKlump\CheckPages\Files\LoadUsers;
use AKlump\CheckPages\Frameworks\Drupal\ValidateSession;
use AKlump\CheckPages\Helpers\FilterUsersByName;
use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Parts\Test;
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
    $cookie = $session->getCookieHeader();
    if (!$cookie) {
      throw new StopRunnerException("No session found for user: $username. Try: export CP_USERNAME=foobar");
    }
    $event->getDriver()->setHeader('Cookie', [$cookie]);

    $test = $event->getTest();

    $validated_user = validateSession($session, $test->getRunner());
    if (!$validated_user->getAccountName()) {
      $validated_user->setAccountName($username);
    }
    setUserVariables($test, $validated_user);

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
  Event::TEST_FINISHED => function ($username, TestEventInterface $event) use ($users) {
    // These have to be deleted before the next test runs or they will be
    // interpolated by \AKlump\CheckPages\Handlers\Value::getSubscribedEvents and
    // include the wrong user values.
    deleteUserVariables($event->getTest());
  },
]);

/**
 * @throws \AKlump\CheckPages\Exceptions\StopRunnerException
 * @throws \GuzzleHttp\Exception\GuzzleException
 */
function validateSession($session, Runner $runner): User {
  // TODO Add caching so this doesn't validate every test
  $user = (new ValidateSession($runner))($session);
  if (!$user) {
    throw new StopRunnerException(sprintf('Session is invalid or expired for user: %s.', $session->getUser()
      ->getAccountName()));
  }

  return $user;
}

// TODO This should be moved to a global area and used by any handler doing this type of thing.
function setUserVariables(Test $test, UserInterface $user) {
  $test->getSuite()->variables()
    ->setItem('user.id', $user->id())
    ->setItem('user.uid', $user->id())
    ->setItem('user.mail', $user->getEmail())
    ->setItem('user.name', $user->getAccountName())
    ->setItem('user.pass', $user->getPassword())
    ->setItem('user.timezone', $user->getTimeZone()->getName());
}

// TODO This should be moved to a global area and used by any handler doing this type of thing.
function deleteUserVariables(Test $test) {
  $test
    ->getSuite()
    ->variables()
    ->removeItem('user.id')
    ->removeItem('user.uid')
    ->removeItem('user.mail')
    ->removeItem('user.name')
    ->removeItem('user.pass')
    ->removeItem('user.timezone');
}
