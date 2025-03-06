<?php

namespace AKlump\CheckPages\Helpers;

use AKlump\CheckPages\Exceptions\StopRunnerException;
use AKlump\CheckPages\HttpClient;
use AKlump\CheckPages\Mixins\Drupal\AuthenticateDrupal7;
use AKlump\CheckPages\Mixins\Drupal\AuthenticateDrupal;
use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\Parts\Test;
use Exception;
use GuzzleHttp\Psr7\Request;
use InvalidArgumentException;
use ReflectionClass;

class AuthenticateProviderFactory {

  private Test $test;

  /**
   * Return the correct AuthenticationInterface instance for the login URL.
   *
   * @param string $login_url The login URL, it maybe be absolute or relative.
   * @param \AKlump\CheckPages\Parts\Test $test
   *
   * @return \AKlump\CheckPages\Helpers\AuthenticationInterface
   *
   * @throws \AKlump\CheckPages\Exceptions\StopRunnerException
   */
  public function __invoke(string $login_url, Test $test): AuthenticationInterface {
    $this->test = $test;

    static $class_names_by_host = [];

    // Ensure this is absolute.
    $login_url = $this->test->getRunner()->withBaseUrl($login_url);

    $host_name = $this->extractHostName($login_url);
    $http_client = $this->createHttpClient();

    try {
      if (empty($class_names_by_host[$host_name])) {
        $class_names_by_host[$host_name] = $this->determineAuthenticationClass($http_client, $login_url);
      }

      $authentication_class = $class_names_by_host[$host_name];
      if (empty($authentication_class)) {
        throw new StopRunnerException(sprintf('Unable to determine authentication class; cannot authenticate against %s', $login_url));
      }

      $this->addClassContextToHttpClient($http_client, $authentication_class);

      return new $authentication_class(
        $http_client,
        $test->getRunner()->getLogFiles(),
        $login_url
      );
    }
    catch (Exception $exception) {
      throw new StopRunnerException($exception->getMessage(), $exception->getCode(), $exception);
    }
  }

  /**
   * @return \AKlump\CheckPages\HttpClient
   */
  private function createHttpClient(): HttpClient {
    return new HttpClient($this->test->getRunner(), $this->test);
  }

  /**
   * @param \AKlump\CheckPages\HttpClient $http_client
   * @param string $absolute_login_url
   *
   * @return string
   * @throws \ReflectionException|\AKlump\CheckPages\Exceptions\StopRunnerException
   */
  private function determineAuthenticationClass(HttpClient $http_client, string $absolute_login_url): string {
    $drupal_version = $this->getMajorDrupalVersion($http_client, $absolute_login_url);
    if (empty($drupal_version)) {
      throw new StopRunnerException('Unable to determine Drupal version.');
    }
    if (7 === $drupal_version) {
      return AuthenticateDrupal7::class;
    }

    return AuthenticateDrupal::class;
  }

  /**
   * @param $http_client
   * @param $absolute_login_url
   *
   * @return int|null
   * @throws \ReflectionException
   */
  private function getMajorDrupalVersion($http_client, $absolute_login_url): ?int {
    $this->addClassContextToHttpClient($http_client, __CLASS__);
    $response = $http_client
      ->setWhyForNextRequestOnly(__METHOD__)
      ->sendRequest(new Request('GET', $absolute_login_url));

    $generator_header = $response->getHeader('X-Generator')[0] ?? '';
    preg_match('/Drupal (\d+)/', $generator_header, $matches);

    return isset($matches[1]) ? (int) $matches[1] : NULL;
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
    $reflection_class = new ReflectionClass($class_name);
    $short_name = $reflection_class->getShortName();

    $suite = new Suite(
      $this->test->getSuite()->id() ?: $short_name,
      $this->test->getRunner()
    );
    $suite_group = $this->test->getSuite()->getGroup();
    if (!empty($suite_group)) {
      $suite->setGroup($suite_group);
    }

    $http_client->dispatchEventsWith(new Test(
      $this->test->id(),
      ['why' => $class_name],
      $suite
    ));
  }

  /**
   * Extract the host name from a URL.
   *
   * @param string $url
   *
   * @return string
   */
  private function extractHostName(string $url): string {
    $host_name = parse_url($url, PHP_URL_HOST);

    if (!$host_name) {
      throw new InvalidArgumentException('Invalid URL provided.');
    }

    return $host_name;
  }
}
