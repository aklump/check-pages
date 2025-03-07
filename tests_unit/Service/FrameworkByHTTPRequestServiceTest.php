<?php

namespace AKlump\CheckPages\Tests\Unit\Service;

use AKlump\CheckPages\Service\FrameworkByHTTPRequestService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\Service\FrameworkByHTTPRequestService
 */
class FrameworkByHTTPRequestServiceTest extends TestCase {

  public static function dataFortestGetFrameworkAndMajorVersionProvider(): array {
    $tests = [];
    $tests[] = [
      ['x-generator' => ['Drupal 8 (https://www.drupal.org)']],
      FrameworkByHTTPRequestService::DRUPAL,
      8,
    ];
    $tests[] = [
      ['x-generator' => ['Drupal 10 (https://www.drupal.org)']],
      FrameworkByHTTPRequestService::DRUPAL,
      10,
    ];
    $tests[] = [
      ['x-generator' => ['Drupal 7 (http://drupal.org)']],
      FrameworkByHTTPRequestService::DRUPAL,
      7,
    ];

    return $tests;
  }

  /**
   * @dataProvider dataFortestGetFrameworkAndMajorVersionProvider
   */
  public function testGetFrameworkAndMajorVersion(array $mocked_headers, string $expected_framework, int $expected_version) {
    $response = $this->createPartialMock(Response::class, ['getHeader']);
    $response->method('getHeader')
      ->willReturnCallback(fn($name) => $mocked_headers[strtolower($name)] ?? []);
    $http_client = $this->createConfiguredMock(Client::class, [
      'sendRequest' => $response,
    ]);
    $service = new FrameworkByHTTPRequestService($http_client);
    $service->request('http://example.com');
    $this->assertSame($expected_framework, $service->getFramework());
    $this->assertSame($expected_version, $service->getMajorVersion());
  }
}
