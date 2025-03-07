<?php

namespace AKlump\CheckPages\Service;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;

/**
 * Read the framework and version using an URL via HTTP request.
 *
 * Use this to check a website if it is Drupal 10, 9, etc.
 */
class FrameworkByHTTPRequestService {

  const DRUPAL = 'Drupal';

  private ClientInterface $client;

  private ?string $framework;

  private ?int $majorVersion;

  public function __construct(ClientInterface $http_client) {
    $this->client = $http_client;
  }

  public function request(string $absolute_login_url): self {
    $this->framework = NULL;
    $this->majorVersion = NULL;
    $response = $this->client->sendRequest(new Request('GET', $absolute_login_url));
    $generator_header = $response->getHeader('X-Generator')[0] ?? '';
    if (preg_match('/Drupal (\d+)/', $generator_header, $matches)) {
      $this->framework = self::DRUPAL;
      $this->majorVersion = (int) $matches[1];
    }

    return $this;
  }

  public function getMajorVersion(): ?int {
    return $this->majorVersion;
  }

  /**
   * @return void
   * @see self::DRUPAL
   */
  public function getFramework(): ?string {
    return $this->framework;
  }

}
