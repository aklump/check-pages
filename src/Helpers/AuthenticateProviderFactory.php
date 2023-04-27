<?php

namespace AKlump\CheckPages\Helpers;

use AKlump\CheckPages\Browser\GuzzleDriver;
use AKlump\CheckPages\Exceptions\StopRunnerException;
use AKlump\CheckPages\HttpClient;
use AKlump\CheckPages\Mixins\Drupal\AuthenticateDrupal7;
use AKlump\CheckPages\Mixins\Drupal\AuthenticateDrupal8;
use AKlump\CheckPages\Parts\Test;
use GuzzleHttp\Psr7\Request;

class AuthenticateProviderFactory {

  /**
   * @param \AKlump\CheckPages\Parts\Test $test
   * @param \AKlump\CheckPages\Files\FilesProviderInterface $log_files
   * @param string $path_to_users_list
   */
  public function __construct(Test $test, string $path_to_users_list) {
    $this->test = $test;
    $this->logFiles = $test->getRunner()->getLogFiles();
    $this->pathToUsers = $path_to_users_list;
  }

  /**
   * Return the correct AuthenticationInterface instance for the login URL.
   *
   * @param string $absolute_login_url
   *
   * @return \AKlump\CheckPages\Helpers\AuthenticationInterface
   *
   * @throws \AKlump\CheckPages\Exceptions\StopRunnerException If the class cannot be determined based on context.
   * @throws \GuzzleHttp\Exception\GuzzleException If the class cannot be determined based on context.
   */
  public function __invoke(string $absolute_login_url): AuthenticationInterface {
    static $classnames;
    $http_client = new HttpClient($this->test->getRunner(), $this->test);
    $cid = parse_url($absolute_login_url, PHP_URL_HOST);
    if (empty($classnames[$cid])) {
      $response = $http_client->sendRequest(new Request('get', $absolute_login_url));
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

    return new $classnames[$cid]($http_client, $this->logFiles, $this->pathToUsers, $absolute_login_url);
  }

}
