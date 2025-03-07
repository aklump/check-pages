<?php

namespace AKlump\CheckPages\Tests\Unit\Helpers;

use AKlump\CheckPages\Exceptions\StopRunnerException;
use AKlump\CheckPages\Files\FilesProviderInterface;
use AKlump\CheckPages\Helpers\AuthenticateProviderFactory;
use AKlump\CheckPages\HttpClient;
use AKlump\CheckPages\Mixins\Drupal\AuthenticateDrupal;
use AKlump\CheckPages\Mixins\Drupal\AuthenticateDrupal7;
use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Parts\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * @covers \AKlump\CheckPages\Helpers\AuthenticateProviderFactory
 * @uses   \AKlump\CheckPages\Service\FrameworkByHTTPRequestService
 * @uses   \AKlump\CheckPages\Parts\Suite
 * @uses   \AKlump\CheckPages\Parts\Test
 * @uses   \AKlump\CheckPages\Traits\HasRunnerTrait
 * @uses   \AKlump\CheckPages\Traits\HasConfigTrait
 * @uses   \AKlump\CheckPages\Mixins\Drupal\AuthenticateDrupal
 * @uses   \AKlump\CheckPages\Mixins\Drupal\AuthenticateDrupal7
 * @uses   \AKlump\CheckPages\Exceptions\StopRunnerException
 */
class AuthenticateProviderFactoryTest extends TestCase {

  public function testStaticCacheReturnsWithoutHttpRequest() {
    // This is here to flush the cache
    new AuthenticateProviderFactory(TRUE);
    $test = $this->getMockedTest();
    $http_client = $this->getDrupalHttpClient(10);

    $http_client->expects($this->once())->method('sendRequest');
    $provider = (new AuthenticateProviderFactory())('/user/login', $test, $http_client);
    $this->assertInstanceOf(AuthenticateDrupal::class, $provider);

    // Make a second call and assert the http request is never sent.
    $http_client->expects($this->never())->method('sendRequest');
    $provider = (new AuthenticateProviderFactory())('/user/login', $test, $http_client);
    $this->assertInstanceOf(AuthenticateDrupal::class, $provider);
  }

  public function testInvokeThrowsExceptionIfUnableToDetermineDrupalVersion() {
    $this->expectException(StopRunnerException::class);
    $this->expectExceptionMessage('Unable to determine Drupal version.');
    $factory = new AuthenticateProviderFactory(TRUE);
    $response = $this->createConfiguredMock(ResponseInterface::class, ['getHeader' => []]);
    $http_client = $this->createConfiguredMock(HttpClient::class, [
      'sendRequest' => $response,
    ]);
    $factory(
      '/user/login',
      $this->getMockedTest(),
      $http_client);
  }

  public static function dataFortestInvokeProvider(): array {
    $tests = [];
    $tests[] = [7, AuthenticateDrupal7::class];
    $tests[] = [8, AuthenticateDrupal::class];
    $tests[] = [9, AuthenticateDrupal::class];
    $tests[] = [10, AuthenticateDrupal::class];
    $tests[] = [11, AuthenticateDrupal::class];

    return $tests;
  }

  /**
   * @dataProvider dataFortestInvokeProvider
   */
  public function testInvoke(int $major_version, string $expected_class) {
    $factory = new AuthenticateProviderFactory(TRUE);
    $provider = $factory(
      '/user/login',
      $this->getMockedTest(),
      $this->getDrupalHttpClient($major_version)
    );
    $this->assertInstanceOf($expected_class, $provider);
  }

  private function getMockedTest() {
    $base_url = 'https://example.com';
    $runner = $this->createMock(Runner::class);
    $runner->method('withBaseUrl')
      ->willReturnCallback(function ($url) use ($base_url) {
        return $base_url . '/' . trim($url, '/');
      });
    $log_files = $this->createMock(FilesProviderInterface::class);
    $runner->method('getLogFiles')->willReturn($log_files);

    return $this->createConfiguredMock(Test::class, [
      'getRunner' => $runner,
    ]);

  }

  private function getDrupalHttpClient(int $major_version) {
    $response = $this->createConfiguredMock(ResponseInterface::class, [
      'getHeader' => [sprintf('Drupal %d (http://drupal.org)', $major_version)],
    ]);

    return $this->createConfiguredMock(HttpClient::class, [
      'sendRequest' => $response,
    ]);
  }
}
