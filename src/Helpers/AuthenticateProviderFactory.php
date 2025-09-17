<?php

namespace AKlump\CheckPages\Helpers;

use AKlump\CheckPages\Exceptions\StopRunnerException;
use AKlump\CheckPages\HttpClient;
use AKlump\CheckPages\Mixins\Drupal\AuthenticateDrupal7;
use AKlump\CheckPages\Mixins\Drupal\AuthenticateDrupal;
use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\Service\FrameworkByHTTPRequestService;
use Exception;
use InvalidArgumentException;
use ReflectionClass;

final class AuthenticateProviderFactory {

  private static array $cached = [];

  private Test $test;

  public function __construct(bool $flush_cache = FALSE) {
    if ($flush_cache) {
      self::$cached = [];
    }
  }

  /**
   * Return the correct AuthenticationInterface instance for the login URL.
   *
   * @param string $login_url The login URL, it maybe be absolute or relative.
   * @param \AKlump\CheckPages\Parts\Test $test
   * @param \AKlump\CheckPages\HttpClient $http_client
   *
   * @return \AKlump\CheckPages\Helpers\AuthenticationInterface
   *
   * @throws \AKlump\CheckPages\Exceptions\StopRunnerException
   */
  public function __invoke(string $login_url, Test $test, HttpClient $http_client): AuthenticationInterface {
    $this->test = $test;

    // Ensure this is absolute.
    $login_url = $this->test->getRunner()->withBaseUrl($login_url);
    $cid = parse_url($login_url, PHP_URL_HOST);
    if (empty($cid)) {
      throw new InvalidArgumentException(sprintf('Invalid URL %s.', $login_url));
    }

    try {
      if (empty(self::$cached[$cid])) {
        self::$cached[$cid] = $this->determineAuthenticationClass($http_client, $login_url);
      }
      $authentication_class = self::$cached[$cid];
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
   * @param \AKlump\CheckPages\HttpClient $http_client
   * @param string $absolute_login_url
   *
   * @return string
   * @throws \ReflectionException|\AKlump\CheckPages\Exceptions\StopRunnerException
   */
  private function determineAuthenticationClass(HttpClient $http_client, string $absolute_login_url): string {
    $this->addClassContextToHttpClient($http_client, __CLASS__);
    $http_client->setWhyForNextRequestOnly(__METHOD__);
    $service = new FrameworkByHTTPRequestService($http_client);
    $drupal_version = $service->request($absolute_login_url)->getMajorVersion();
    if (empty($drupal_version)) {
      throw new StopRunnerException('Unable to determine Drupal version.');
    }
    if (7 === $drupal_version) {
      return AuthenticateDrupal7::class;
    }

    return AuthenticateDrupal::class;
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
}
