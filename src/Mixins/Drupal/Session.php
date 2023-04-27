<?php

namespace AKlump\CheckPages\Mixins\Drupal;

use AKlump\CheckPages\Helpers\AuthenticateProviderFactory;
use AKlump\CheckPages\Parts\Test;
final class Session {

  /**
   * @var array
   */
  private $mixinConfig;

  public function __construct(array $mixin_config) {
    $this->mixinConfig = $mixin_config;
  }

  public function __invoke(string $username, Test $test) {
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

      $factory = new AuthenticateProviderFactory();
      $auth = $factory->get($runner->getLogFiles(), $path_to_users_list, $absolute_login_url);

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
  }
}
