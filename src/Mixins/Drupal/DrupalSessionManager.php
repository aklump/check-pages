<?php

namespace AKlump\CheckPages\Mixins\Drupal;

use AKlump\CheckPages\Browser\Session;
use AKlump\CheckPages\Browser\SessionInterface;
use AKlump\CheckPages\DataStructure\User;
use AKlump\CheckPages\DataStructure\UserInterface;
use AKlump\CheckPages\Files\FilesProviderInterface;
use AKlump\CheckPages\Files\LoadUsers;
use AKlump\CheckPages\Helpers\AuthenticateProviderFactory;
use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\Variables;
use InvalidArgumentException;
use RuntimeException;

final class DrupalSessionManager implements SessionInterface {

  private array $mixinConfig;

  private UserInterface $user;

  /**
   * @var \AKlump\CheckPages\Variables
   */
  private Variables $variables;

  public function __construct(array $mixin_config) {
    $this->mixinConfig = $mixin_config;
  }

  public function __invoke(string $username, Test $test): Variables {
    $runner = $test->getRunner();

    static $sessions;
    if (is_null($sessions)) {
      $sessions = $runner->getStorage()->get('drupal.sessions') ?? [];
    }
    $this->checkSessionExpiry($username, $sessions);
    $this->createNewSession($username, $sessions, $test);

    $this->variables = $this->setUpUserVariables($username, $sessions);

    return $this->variables;
  }

  private function checkSessionExpiry(string $username, array &$sessions): void {
    if (empty($sessions[$username]['expires']) || $sessions[$username]['expires'] < time()) {
      unset($sessions[$username]);
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
  private function createNewSession(string $username, array &$sessions, Test $test): void {
    if (!empty($sessions[$username])) {
      return;
    }
    $this->setUser((new User($username, NULL)));

    $test->addBadge('🔐');
    $runner = $test->getRunner();
    $user = $this->getUser($runner->getFiles(), $username);
    $login_url = $this->mixinConfig['login_url'] ?? '/user/login';
    $auth = (new AuthenticateProviderFactory())($login_url, $test);
    $auth->login($user);
    $account = $user->jsonSerialize();
    if (empty($account['uid'])) {
      throw new RuntimeException(sprintf('Could not determine user ID for %s', $username));
    }

    $sessions[$username] = [
      'cookie' => $auth->getSessionCookie(),
      'expires' => $auth->getSessionExpires(),
      'account' => $account,
      'csrf_token' => $auth->getCsrfToken(),
    ];
    $runner->getStorage()->set('drupal.sessions', $sessions);
  }

  private function setUpUserVariables(string $username, array $sessions): Variables {
    $variables = new Variables();
    $variables
      ->setItem('user.id', $sessions[$username]['account']['uid'])
      ->setItem('user.uid', $sessions[$username]['account']['uid'])
      ->setItem('user.mail', $sessions[$username]['account']['mail'])
      ->setItem('user.name', $sessions[$username]['account']['name'])
      ->setItem('user.pass', $sessions[$username]['account']['pass'])
      ->setItem('user.csrf', $sessions[$username]['csrf_token'])
      ->setItem('user.session_cookie', $sessions[$username]['cookie'])
      ->setItem('user.session_expires', $sessions[$username]['cookie']);

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
    $user = array_filter($users, function (User $user) use ($username) {
      return $user->getAccountName() === $username;
    })[0];
    if (empty($user)) {
      throw new RuntimeException(sprintf('No record for "%s" found.', $username));
    }

    return $user;
  }

  public function getSessionCookie(Test $test): string {
    $runner = $test->getRunner();

    static $sessions;
    if (is_null($sessions)) {
      $sessions = $runner->getStorage()->get('drupal.sessions') ?? [];
    }
    $username = $this->user->getAccountName();
    $this->checkSessionExpiry($username, $sessions);
    $this->createNewSession($username, $sessions, $test);
    $vars = $this->setUpUserVariables($username, $sessions);

    return $vars->getItem('user.session_cookie') ?? '';
  }

  public function setUser(UserInterface $user) {
    $this->user = $user;
  }

  public function getSession(): Session {
    if (!isset($this->variables)) {
      throw new RuntimeException('Session has not been created yet.');
    }
    $account = new User(
      $this->getItem('user.name'),
      $this->getItem('user.pass'),
    );
    $session = new Session();
    $session->setUser($account);
    $cookie = $this->getItem('user.session_cookie');
    $session->setSessionCookie($this->getItem('user.session_cookie'));
  }
}
