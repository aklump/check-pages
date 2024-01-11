<?php

namespace AKlump\CheckPages\Mixins\Drupal;

use AKlump\CheckPages\Helpers\AuthenticateProviderFactory;
use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\Variables;

final class Session {

  private array $mixinConfig;

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
    $this->createNewSession($username, $sessions, $runner, $test);

    return $this->setUpUserVariables($username, $sessions);
  }

  private function checkSessionExpiry(string $username, array &$sessions): void {
    if (empty($sessions[$username]['expires']) || $sessions[$username]['expires'] < time()) {
      unset($sessions[$username]);
    }
  }

  private function createNewSession(string $username, array &$sessions, $runner, Test $test): void {
    if (empty($sessions[$username])) {
      $test->addBadge('🔐');
      if (!array_key_exists('users', $this->mixinConfig)) {
        throw new \InvalidArgumentException('You must provide a filepath to the users list as "users".');
      }

      // Load our non-version username/password index.
      if (empty($this->mixinConfig['users'])) {
        throw new \RuntimeException('Missing value for "users" in the drupal mixin.');
      }
      $path_to_users_list = $runner->getFiles()
                              ->tryResolveFile($this->mixinConfig['users'], [
                                'yaml',
                                'yml',
                              ])[0];
      $login_url = $this->mixinConfig['login_url'] ?? '/user/login';
      $absolute_login_url = $runner->withBaseUrl($login_url);

      $factory = new AuthenticateProviderFactory($test, $path_to_users_list);
      $auth = $factory($absolute_login_url);

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

}
