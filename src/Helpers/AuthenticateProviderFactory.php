<?php

namespace AKlump\CheckPages\Helpers;

use AKlump\CheckPages\Browser\GuzzleDriver;
use AKlump\CheckPages\Exceptions\StopRunnerException;
use AKlump\CheckPages\HttpClient;
use AKlump\CheckPages\Mixins\Drupal\AuthenticateDrupal7;
use AKlump\CheckPages\Mixins\Drupal\AuthenticateDrupal8;
use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\Parts\Test;
use GuzzleHttp\Psr7\Request;
use ReflectionClass;

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
    $http_client = $this->createHttpClient();
    $host_name = parse_url($absolute_login_url, PHP_URL_HOST);

    if (empty($classnames[$host_name])) {
      $classnames[$host_name] = $this->determineClassName($http_client, $absolute_login_url);
    }

    if (empty($classnames[$host_name])) {
      throw new StopRunnerException(sprintf('Unable to determine authentication class; cannot authenticate against %s', $absolute_login_url));
    }

    $this->addClassContextToHttpClient($http_client, $classnames[$host_name]);

    return new $classnames[$host_name]($http_client, $this->logFiles, $this->pathToUsers, $absolute_login_url);
  }

  private function createHttpClient(): HttpClient {
    return new HttpClient($this->test->getRunner(), $this->test);
  }

  private function determineClassName(HttpClient $http_client, string $absolute_login_url): string {
    $major_version = $this->getMajorDrupalVersion($http_client, $absolute_login_url);
    switch ($major_version) {
      case 7:
        return AuthenticateDrupal7::class;
      default:
        return AuthenticateDrupal8::class;
    }
  }

  private function getMajorDrupalVersion($http_client, $absolute_login_url): ?int {
    $this->addClassContextToHttpClient($http_client, __CLASS__);
    $response = $http_client
      ->setWhyForNextRequestOnly(__METHOD__)
      ->sendRequest(new Request('get', $absolute_login_url));
    $generator = $response->getHeader('X-Generator')[0] ?? '';
    preg_match('/Drupal (\d+)/', $generator, $matches);

    return intval($matches[1]) ?? NULL;
  }

  /**
   * Configure the http client to point to a FQN class.
   *
   * @param \AKlump\CheckPages\HttpClient $http_client
   * @param string $class_name
   *
   * @return void
   * @throws \ReflectionException
   */
  private function addClassContextToHttpClient(HttpClient $http_client, string $class_name): void {
    $ref = new ReflectionClass($class_name);
    $shortname = $ref->getShortName();
    $suite_id = $this->test->getSuite()->id() ?: $shortname;
    $suite = new Suite($suite_id, [], $this->test->getRunner());
    $suite_group = $this->test->getSuite()->getGroup();
    if (!empty($suite_group)) {
      $suite->setGroup($suite_group);
    }
    $http_client->dispatchEventsWith(new Test($this->test->id(), ['why' => $class_name], $suite));
  }

}
