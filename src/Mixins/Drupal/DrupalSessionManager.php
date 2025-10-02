<?php

namespace AKlump\CheckPages\Mixins\Drupal;

use AKlump\CheckPages\Browser\Session;
use AKlump\CheckPages\DataStructure\User;
use AKlump\CheckPages\DataStructure\UserInterface;
use AKlump\CheckPages\Files\FilesProviderInterface;
use AKlump\CheckPages\Files\LoadConfig;
use AKlump\CheckPages\Files\LoadUsers;
use AKlump\CheckPages\Helpers\AuthenticateProviderFactory;
use AKlump\CheckPages\Helpers\AuthenticationInterface;
use AKlump\CheckPages\HttpClient;
use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\Variables;
use InvalidArgumentException;
use RuntimeException;

final class DrupalSessionManager {

  private static array $sessionsData = [];

  /**
   * @var \AKlump\CheckPages\Browser\Session
   */
  private Session $session;

  private array $mixinConfig;

  public function __construct(array $mixin_config, bool $flush_cache = FALSE) {
    $this->mixinConfig = $mixin_config;
    if ($flush_cache) {
      self::$sessionsData = [];
    }
  }

  public function __invoke(string $username, Test $test): Variables {
    $runner = $test->getRunner();
    $user = $this->getUser($runner->getFiles(), $username);
    $storage = $runner->getStorage();
    if (is_null(self::$sessionsData)) {
      self::$sessionsData = $storage->get('drupal.sessions') ?? [];
    }
    $this->checkSessionExpiry($username);
    // ... checking the session expiry may have erased what had been set.
    if (empty(self::$sessionsData[$username])) {
      $auth = $this->createNewSession($user, $test);
      self::$sessionsData[$username] = $auth;
      $storage->set('drupal.sessions', self::$sessionsData);
    }
    $this->session = self::$sessionsData[$username]->getSession();

    return $this->createUserSessionVariables(
      self::$sessionsData[$username]->getSession()->getUser(),
      self::$sessionsData[$username]);
  }

  private function checkSessionExpiry(string $username): void {
    if (!isset(self::$sessionsData[$username])) {
      return;
    }
    $expiry = self::$sessionsData[$username]->getSession()->getExpires();
    if (empty($expiry) || $expiry < time()) {
      unset(self::$sessionsData[$username]);
    }
  }

  /**
   * @throws \AKlump\CheckPages\Exceptions\StopRunnerException
   */
  private function createNewSession(UserInterface $user, Test $test): AuthenticationInterface {
    $test->addBadge('🔐');
    $login_url = $this->mixinConfig['login_url'] ?? '/user/login';
    $username = $user->getAccountName();
    $http_client = new HttpClient($test->getRunner(), $test);
    $auth = (new AuthenticateProviderFactory())($login_url, $test, $http_client);
    $auth->login($user);
    if (!$user->id()) {
      throw new RuntimeException(sprintf('Could not determine user ID for %s', $username));
    }

    return $auth;
  }

  /**
   * @param \AKlump\CheckPages\DataStructure\UserInterface $user
   * @param \AKlump\CheckPages\Helpers\AuthenticationInterface|null $auth
   *
   * @return \AKlump\CheckPages\Variables Session data will overwrite $user
   * data, when it is present.
   */
  private function createUserSessionVariables(UserInterface $user, AuthenticationInterface $auth = NULL): Variables {
    $variables = (new Variables())
      ->setItem('user.id', $user->id())
      ->setItem('user.uid', $user->id())
      ->setItem('user.mail', $user->getEmail())
      ->setItem('user.name', $user->getAccountName())
      ->setItem('user.pass', $user->getPassword());
    if (isset($auth)) {
      $session = $auth->getSession();
      $variables
        ->setItem('user.csrf', $auth->getCsrfToken())
        ->setItem('user.session_cookie', $session->getCookieHeader())
        ->setItem('user.session_expires', $session->getExpires());
    }

    return $variables;
  }

  private function getUser(FilesProviderInterface $files, string $username): User {
    if (!array_key_exists('users', $this->mixinConfig)) {
      throw new InvalidArgumentException('You must provide a filepath to the users list as "users".');
    }
    if (empty($this->mixinConfig['users'])) {
      throw new RuntimeException('Missing value for "users" in the drupal mixin.');
    }
    $path_to_users_data = $files->tryResolveFile($this->mixinConfig['users'], LoadConfig::CONFIG_EXTENSIONS)[0] ?? '';
    $users = (new LoadUsers())($path_to_users_data);
    $user = array_values(array_filter($users, function (User $user) use ($username) {
      return $user->getAccountName() === $username;
    }))[0];
    if (empty($user)) {
      throw new RuntimeException(sprintf('No record for "%s" found.', $username));
    }

    return $user;
  }

  public function getSession(): Session {
    return $this->session;
  }
}
