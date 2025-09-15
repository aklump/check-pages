<?php

/**
 * @file
 * Support for Drupal websites.
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
 *    - ${user.id} or ${user.uid}
 *    - ${user.mail}
 *    - ${user.name}
 *    - ${user.pass}
 *    - ${user.csrf}
 *
 * For example you can write: visit: /user/{foo.uid}/edit
 */

namespace AKlump\CheckPages\Mixins\Drupal;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\DriverEventInterface;
use AKlump\CheckPages\Event\SuiteEventInterface;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\LoftLib\Bash\Color;
use Exception;

/** @var \AKlump\CheckPages\Parts\Runner $runner */
/** @var array $mixin_config */

/**
 * @param string $username
 * @param \AKlump\CheckPages\Parts\Test $test
 *
 * @return array|mixed
 * @throws \AKlump\CheckPages\Exceptions\StopRunnerException
 */

$session_manager = new DrupalSessionManager($mixin_config);

add_test_option('user', [
  Event::TEST_CREATED => function ($username, TestEventInterface $event) use ($session_manager) {
    if (!empty($username)) {
      $session_vars = $session_manager($username, $event->getTest());
      foreach ($session_vars as $name => $value) {
        $event->getTest()->getSuite()->variables()->setItem($name, $value);
      }
    }
  },

  /**
   * This is important because the user scope is only for the test.  If these
   * aren't removed then it's possible interpolation gets whacked out.  If a
   * user wants to use one of these values in another test, they must capture
   * the value and set it.
   */
  Event::TEST_FINISHED => function ($username, TestEventInterface $event) use ($session_manager) {
    if (!empty($username)) {
      $session_vars = $session_manager($username, $event->getTest());
      foreach ($session_vars as $name => $value) {
        $event->getTest()->getSuite()->variables()->removeItem($name);
      }
    }
  },

  Event::REQUEST_CREATED => function ($username, DriverEventInterface $event) use ($session_manager) {
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
    $session = $session_manager->getSession();
    $event->getDriver()
      ->setHeader('Cookie', $session->getCookieHeader());
  },
]);

/**
 * Remove any log files for a fresh slate.
 */
respond_to(Event::SUITE_STARTED, function (SuiteEventInterface $suite_event) {
  try {
    $log_files = $suite_event->getSuite()
      ->getRunner()
      ->getLogFiles();

    if (strstr(AuthenticateDrupal::LOG_FILE_PATH, '/') !== FALSE) {
      $top_dir = explode('/', AuthenticateDrupal::LOG_FILE_PATH)[0];
      $filepath = $log_files->tryResolveDir($top_dir)[0];
      $log_files->tryEmptyDir($filepath);
    }
    else {
      $filepath = $log_files->tryResolveFile(AuthenticateDrupal::LOG_FILE_PATH)[0];
      unlink($filepath);
    }
  }
  catch (Exception $exception) {
    // Purposefully left blank, because there is nothing to clear out.
  }
});

/**
 * Delete any sessions that were captured in Storage.  This is more reliable
 * than keeping the sessions across runner executions and to depend on the
 * session expiry, which may lead to sessions that no longer exist on the
 * remote, and subsequently annoying test failures.  The performance hit of this
 * has been measured and is very small.  The reliability gain far exceeds the
 * slight hit in performance.
 */
respond_to(Event::RUNNER_FINISHED, function () use ($runner) {
  $runner->getStorage()->delete('drupal.sessions');
});

