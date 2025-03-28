<?php

namespace AKlump\CheckPages\Mixins\Drupal;

use AKlump\CheckPages\Browser\Session;
use AKlump\CheckPages\DataStructure\User;
use AKlump\CheckPages\Files\FilesProviderInterface;
use AKlump\CheckPages\Files\LoadUsers;
use AKlump\CheckPages\Helpers\AuthenticateProviderFactory;
use AKlump\CheckPages\HttpClient;
use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\Variables;
use InvalidArgumentException;
use RuntimeException;

final class DrupalSessionManager {

  private static array $sessions = [];

  private array $mixinConfig;

  /**
   * @var \AKlump\CheckPages\Variables
   */
  private Variables $variables;

  public function __construct(array $mixin_config, bool $flush_cache = FALSE) {
    $this->mixinConfig = $mixin_config;
    if ($flush_cache) {
      self::$sessions = [];
    }
  }

  public function __invoke(string $username, Test $test): Variables {
    $runner = $test->getRunner();
    if (is_null(self::$sessions)) {
      self::$sessions = $runner->getStorage()->get('drupal.sessions') ?? [];
    }
    $this->checkSessionExpiry($username);
    if (empty(self::$sessions[$username])) {
      $this->createNewSession($username, $test);
    }
    $this->variables = $this->setUpUserVariables($username);

    return $this->variables;
  }

  private function checkSessionExpiry(string $username): void {
    if (!isset(self::$sessions[$username])) {
      return;
    }
    if (empty(self::$sessions[$username]['expires']) || self::$sessions[$username]['expires'] < time()) {
      unset(self::$sessions[$username]);
    }
  }

  /**
   * @param string $username
   * @param array $sessions
   * @param \AKlump\CheckPages\Parts\Test $test
   *
   * @return void
   * @throws \AKlump\CheckPages\Exceptions\StopRunnerException
   */
  private function createNewSession(string $username, Test $test): void {
    $test->addBadge('🔐');
    $runner = $test->getRunner();
    $user = $this->getUser($runner->getFiles(), $username);
    $login_url = $this->mixinConfig['login_url'] ?? '/user/login';
    $http_client = new HttpClient($test->getRunner(), $test);
    $auth = (new AuthenticateProviderFactory())($login_url, $test, $http_client);
    $auth->login($user);
    $account = $user->jsonSerialize();
    if (empty($account['uid'])) {
      throw new RuntimeException(sprintf('Could not determine user ID for %s', $username));
    }

    self::$sessions[$username] = [
      'cookie' => $auth->getSessionCookie(),
      'expires' => $auth->getSessionExpires(),
      'account' => $account,
      'csrf_token' => $auth->getCsrfToken(),
    ];
    $runner->getStorage()->set('drupal.sessions', self::$sessions);
  }

  private function setUpUserVariables(string $username): Variables {
    $variables = new Variables();
    $variables
      ->setItem('user.id', self::$sessions[$username]['account']['uid'])
      ->setItem('user.uid', self::$sessions[$username]['account']['uid'])
      ->setItem('user.mail', self::$sessions[$username]['account']['mail'])
      ->setItem('user.name', self::$sessions[$username]['account']['name'])
      ->setItem('user.pass', self::$sessions[$username]['account']['pass'])
      ->setItem('user.csrf', self::$sessions[$username]['csrf_token'])
      ->setItem('user.session_cookie', self::$sessions[$username]['cookie'])
      ->setItem('user.session_expires', self::$sessions[$username]['expires']);

    return $variables;
  }

  private function getUser(FilesProviderInterface $files, string $username): User {
    if (!array_key_exists('users', $this->mixinConfig)) {
      throw new InvalidArgumentException('You must provide a filepath to the users list as "users".');
    }
    if (empty($this->mixinConfig['users'])) {
      throw new RuntimeException('Missing value for "users" in the drupal mixin.');
    }
    $path_to_users_data = $files->tryResolveFile($this->mixinConfig['users'], [
      'yaml',
      'yml',
    ])[0] ?? '';
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
    if (!isset($this->variables)) {
      throw new RuntimeException('Session has not been created yet.');
    }
    $account = new User(
      $this->variables->getItem('user.name'),
      $this->variables->getItem('user.pass'),
    );
    $session = new Session();
    $session->setUser($account);
    $cookie = $this->variables->getItem('user.session_cookie');
    list($name, $value) = explode('=', $cookie, 2);
    $session->setName($name);
    $session->setValue($value);

    return $session;
  }
}
