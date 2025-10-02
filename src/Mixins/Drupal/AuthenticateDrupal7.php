<?php

namespace AKlump\CheckPages\Mixins\Drupal;

use AKlump\CheckPages\Browser\GuzzleDriver;
use AKlump\CheckPages\Browser\Session;
use AKlump\CheckPages\DataStructure\UserInterface;
use AKlump\CheckPages\Files\FilesProviderInterface;
use AKlump\CheckPages\Files\HttpLogging;
use AKlump\CheckPages\HttpClient;
use DOMElement;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

final class AuthenticateDrupal7 extends AuthenticateDrupal {

  /**
   * AuthenticateDrupal constructor.
   *
   * @param \AKlump\CheckPages\HttpClient $http_client
   * @param \AKlump\CheckPages\Files\FilesProviderInterface $log_files
   * @param string $path_to_users_login_data
   *   The resolved path to the JSON or YAML file for the users.
   * @param string $absolute_login_url
   *   The absolute URL to the login form.
   * @param string $form_selector
   *   A CSS selector to find the login form on in the DOM of the
   *   $absolute_login_url.
   * @param string $form_id
   *   The value of the hidden input name=form_id.
   */
  public function __construct(
    HttpClient $http_client,
    FilesProviderInterface $log_files,
    string $absolute_login_url,
    string $form_selector = 'form[action="/user/login"]',
    string $form_id = 'user_login'
  ) {
    parent::__construct($http_client, $log_files, $absolute_login_url, $form_selector, $form_id);
  }

  public function login(UserInterface $user) {
    $username = $user->getAccountName();
    $password = $user->getPassword();

    // Scrape the form_build_id, which is necessary lest the form not submit.
    $log_file_contents = HttpLogging::request('Scrape the login form', 'get', $this->loginUrl);
    $this->writeLogFile($log_file_contents);

    $this->response = $this->httpClient
      ->setWhyForNextRequestOnly(__METHOD__)
      ->sendRequest(new Request('get', $this->loginUrl));
    $body = strval($this->response->getBody());

    $crawler = new Crawler($body);
    $form_build_id = $crawler->filter($this->formSelector . ' input[name="form_build_id"]')
      ->getNode(0);
    if ($form_build_id instanceof DOMElement) {
      $form_build_id = $form_build_id->getAttribute('value');
    }
    else {
      throw new RuntimeException(sprintf('Login form missing from %s', $this->loginUrl));
    }

    // Now that we have a complete form, try to log in and get a session.
    $jar = new CookieJar();
    try {
      $failed_message = [
        sprintf('Login failed for username "%s" with password "%s".',
          $username,
          substr($password, 0, 2) . str_repeat('*', strlen($password) - 2)
        ),
      ];
      $failed = FALSE;

      $form_params = [
        'op' => 'Log in',
        'name' => $username,
        'pass' => $password,
        'form_id' => $this->formId,
        'form_build_id' => $form_build_id,
      ];
      $log_file_contents = HttpLogging::request('Submit login form', 'post', $this->loginUrl, [
        'content-type' => 'application/x-www-form-urlencoded',
      ], $form_params);
      $this->writeLogFile($log_file_contents);

      $guzzle = new GuzzleDriver();
      $this->response = $guzzle
        ->getClient()
        ->request('POST', $this->loginUrl, [
          'cookies' => $jar,
          'form_params' => $form_params,
        ]);

      //      $this->response = $this->httpClient->sendRequest(new Request('post', $this->loginUrl, [
      //        'cookies' => $jar,
      //        'form_params' => $form_params,
      //      ]));
    }
    catch (GuzzleException $exception) {
      $failed = TRUE;
      $failed_message[] = $exception->getMessage();
    }

    if (!$failed) {
      // Look for the login form again in the page response, if it's still here,
      // that means login failed.  We could also look in the headers for
      // "Set-Cookie" to see if it changed from the request above, if this doesn't
      // prove to work over time.  A changed session cookie indicates a new
      // session was created, but not necessarily that the login succeeded.
      $crawler = new Crawler(strval($this->response->getBody()));
      $failed = $crawler->filter($this->formSelector)->count() > 0;
    }

    // TODO Figure out how to read the session-based messages, so we can add them from Drupal to $failed_message for easier debugging.

    if ($failed) {
      throw new RuntimeException(implode(PHP_EOL, $failed_message));
    }

    $session_cookie = array_values(array_filter($jar->toArray(), function ($item) {
      return strpos($item['Name'], 'SESS') !== FALSE;
    }))[0] ?? NULL;
    if (empty($session_cookie)) {
      throw new RuntimeException(sprintf('Did not obtain session cookie for username "%s".', $username));
    }

    $name = $session_cookie['Name'] ?? $session_cookie['name'] ?? '';
    $value = $session_cookie['Value'] ?? $session_cookie['value'] ?? '';
    $expires = $session_cookie['Expires'] ?? $session_cookie['expires'] ?? '';
    if (!$name || !$value || !$expires) {
      throw new RuntimeException(sprintf('Failed to establish session for username "%s". SESSION response header is either missing or incomplete.', $user->getAccountName()));
    }

    $this->session = new Session();
    $this->session->setName($name);
    $this->session->setValue($value);
    $this->session->setExpires($expires);
    $this->session->setUser($user);

    $id = $this->requestUserId($user);
    if ($id) {
      $user->setId($id);
    }

    $mail = $this->requestUserEmail($user);
    if ($mail) {
      $user->setEmail($mail);
    }
    $this->populateUserId($user);
    $this->populateUserEmail($user);
  }

  public function getCsrfToken(): string {
    return '';
  }

  private function populateUserId(UserInterface $user) {
    if (!$user->id()) {
      $body = strval($this->getResponse()->getBody());
      if (preg_match('/"uid"\:"(\d+?)"/', $body, $matches)) {
        $user->setId(intval($matches[1]));
      }
    }
  }

  private function populateUserEmail(UserInterface $user) {
    if (!$user->getEmail()) {
      $this->requestUserEmail($user);
    }
  }

}
