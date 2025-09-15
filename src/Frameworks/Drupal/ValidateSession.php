<?php

namespace AKlump\CheckPages\Frameworks\Drupal;

use AKlump\CheckPages\Browser\GuzzleDriver;
use AKlump\CheckPages\Browser\Session;
use AKlump\CheckPages\DataStructure\User;
use GuzzleHttp\RequestOptions;
use Symfony\Component\DomCrawler\Crawler;

class ValidateSession {

  private string $baseUrl;

  public function __construct(string $base_url) {
    $this->baseUrl = $base_url;
  }

  /**
   * @var \AKlump\CheckPages\Browser\Session
   */
  private Session $session;

  /**
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function __invoke(Session $session): ?User {
    $this->session = $session;
    $html = $this->get($this->baseUrl . '/user');
    $user_id = $this->scrapeUserId($html);
    if (!$user_id) {
      return NULL;
    }
    $user = new User();
    $user->setId($user_id);

    $html = $this->get($this->baseUrl . '/user/' . $user_id . '/edit');
    $user->setAccountName($this->scrapeUsername($html));
    $user->setEmail($this->scrapeEmail($html));
    if ($timezone_name = $this->scrapeTimeZoneName($html)) {
      $user->setTimeZone(timezone_open($timezone_name));
    }

    return $user;
  }

  private function scrapeUsername(string $html): string {
    preg_match('#<input.+?name="name".+?value="(.+?)"#', $html, $matches);

    return $matches[1] ?? '';
  }

  private function scrapeTimeZoneName(string $html): string {
    $crawler = new Crawler($html);
    $selected_option = $crawler->filter('select[name="timezone"] option[selected]');
    if ($selected_option) {
      return $selected_option->attr('value');
    }

    return '';
  }

  /**
   * @param $url
   *
   * @return string
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private function get($url): string {
    $guzzle = new GuzzleDriver();
    $response = $guzzle->getClient([
      'headers' => [
        'Cookie' => $this->session->getCookieHeader(),
      ],
      RequestOptions::ALLOW_REDIRECTS => [
        'max' => 2,
      ],
    ])->get($url);

    return $response->getBody()->getContents();
  }

  private function scrapeEmail(string $html): string {
    preg_match('#<input.+?name="mail".+?value="(.+?)"#', $html, $matches);

    return $matches[1] ?? '';
  }

  private function scrapeUserId(string $html): string {
    preg_match('#<link rel="canonical" href=".+/user/(\d+)" />#', $html, $matches);

    return $matches[1] ?? '';
  }
}
