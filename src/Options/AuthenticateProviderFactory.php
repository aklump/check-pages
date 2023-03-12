<?php

namespace AKlump\CheckPages\Options;

use AKlump\CheckPages\Browser\GuzzleDriver;
use AKlump\CheckPages\Exceptions\StopRunnerException;

class AuthenticateProviderFactory {

  /**
   * Return the correct AuthenticationInterface instance for the login URL.
   *
   * @param string $path_to_users_list
   * @param string $absolute_login_url
   *
   * @return \AKlump\CheckPages\Options\AuthenticationInterface
   *
   * @throws \AKlump\CheckPages\Exceptions\StopRunnerException
   *   If the class cannot be determined based on context.
   */
  public function get(string $path_to_users_list, string $absolute_login_url): AuthenticationInterface {
    static $classnames;
    $cid = parse_url($absolute_login_url, PHP_URL_HOST);
    if (empty($classnames[$cid])) {
      $authenticator = new GuzzleDriver();
      $response = $authenticator->getClient()->get($absolute_login_url);
      $generator = $response->getHeader('X-Generator')[0] ?? '';
      if (preg_match('/Drupal (\d+)/', $generator, $matches)) {
        list(, $major_version) = $matches;
        switch ($major_version) {
          case 7:
            $classnames[$cid] = AuthenticateDrupal7::class;
            break;
          default:
            $classnames[$cid] = AuthenticateDrupal8::class;
            break;
        }
      }
    }
    if (empty($classnames[$cid])) {
      throw new StopRunnerException(sprintf('Unable to determine authentication class; cannot authenticate against %s', $absolute_login_url));
    }

    return new $classnames[$cid]($path_to_users_list, $absolute_login_url);
  }

}
