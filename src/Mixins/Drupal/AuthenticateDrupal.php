<?php

namespace AKlump\CheckPages\Mixins\Drupal;

use AKlump\CheckPages\Browser\GuzzleDriver;
use AKlump\CheckPages\Browser\Session;
use AKlump\CheckPages\Browser\SessionInterface;
use AKlump\CheckPages\DataStructure\UserInterface;
use AKlump\CheckPages\Files\FilesProviderInterface;
use AKlump\CheckPages\Files\HttpLogging;
use AKlump\CheckPages\Helpers\AuthenticationInterface;
use AKlump\CheckPages\HttpClient;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use RuntimeException;
use Spatie\Url\Url;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Base class for Drupal Authentication.
 */
class AuthenticateDrupal implements AuthenticationInterface {

  const LOG_FILE_PATH = 'drupal/authenticate.http';

  /**
   * @var \AKlump\CheckPages\Browser\SessionInterface
   */
  protected $session;

  /**
   * @var mixed|string
   */
  private $csrfToken;

  /**
   * The most recent authentication request response.
   *
   * @var \GuzzleHttp\Psr7\Response
   */
  protected $response;

  /**
   * @var \AKlump\CheckPages\Files\FilesProviderInterface
   */
  protected $logFiles;

  /**
   * @var string
   */
  protected $loginUrl;

  /**
   * @var string
   */
  protected $formId;

  /**
   * @var string
   */
  protected $formSelector;

  /**
   * @var \AKlump\CheckPages\HttpClient
   */
  protected HttpClient $httpClient;

  /**
   * AuthenticateDrupal constructor.
   *
   * @param string $absolute_login_url
   *   The absolute URL to the login form.
   * @param string $form_id
   *   The value of the hidden input name=form_id.
   * @param string $form_selector
   *   A CSS selector to find the login form on in the DOM of the
   *   $absolute_login_url.
   */
  public function __construct(
    HttpClient $http_client,
    FilesProviderInterface $log_files,
    string $absolute_login_url,
    string $form_selector = 'form.user-login-form',
    string $form_id = 'user_login_form'
  ) {
    $this->httpClient = $http_client;
    $this->logFiles = $log_files;
    $this->loginUrl = $absolute_login_url;
    $this->formId = $form_id;
    $this->formSelector = $form_selector;
  }

  /**
   * @param UserInterface $user
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function login(UserInterface $user) {
    try {
      $this->establishUserSession($user);
    }
    catch (\Exception $exception) {
      $failed_message = [];
      $password = $user->getPassword();
      $failed_message[] = sprintf('Login failed for username "%s" with password "%s".',
        $user->getAccountName(),
        substr($password, 0, 2) . str_repeat('*', strlen($password) - 2)
      );
      $failed_message[] = $exception->getMessage();
      $failed_message = implode(PHP_EOL, $failed_message);
      throw new RuntimeException($failed_message, 0, $exception);
    }

    // It may be that this gets set in during the session establishment, so
    // we'll check first before sending off another request.
    if (!$user->id()
      && ($id = $this->requestUserId($user))) {
      $user->setId($id);
    }
    if (!$user->getEmail()
      && ($mail = $this->requestUserEmail($user))) {
      $user->setEmail($mail);
    }
  }

  protected function writeLogFile(string $contents) {
    $filepath = $this->logFiles->tryResolveFile(self::LOG_FILE_PATH, [], FilesProviderInterface::RESOLVE_NON_EXISTENT_PATHS)[0];
    $this->logFiles->tryCreateDir(dirname($filepath));
    $fp = fopen($filepath, 'a');
    fwrite($fp, $contents . PHP_EOL);
    fclose($fp);
  }

  /**
   * Perform one or more requests to obtain the user ID.
   *
   * @param \AKlump\CheckPages\DataStructure\UserInterface $user
   *
   * @return int
   *   The User ID if it can be deteremine.
   *
   * @see \AKlump\CheckPages\Helpers\AuthenticateDrupal::login()
   */
  protected function requestUserId(UserInterface $user): int {
    $url = Url::fromString($this->loginUrl);
    $url = (string) $url->withPath('/user');
    $cookie_header = $this->getSession()->getCookieHeader();
    try {

      $log_file_contents = HttpLogging::request('Request the user ID', 'get', $url, [
        'Cookie' => $cookie_header,
      ]);
      $this->writeLogFile($log_file_contents);

      $this->httpClient
        ->setWhyForNextRequestOnly(__METHOD__)
        ->sendRequest(new Request('get', $url, ['Cookie' => $cookie_header]));
      $request_result = $this->httpClient->getDriver();

      // If the user page is not aliased the UID will appear in the location bar.
      $location = $request_result->getLocation();
      if (preg_match('/user\/(\d+)/', $location, $matches)) {
        return intval($matches[1]);
      }

      // Sometimes it appears in a <link> tag.
      $body = strval($request_result->getResponse()->getBody());

      // Look for the user edit URL pattern, which would probably be in a
      // primary tab.
      preg_match_all('/user\/(\d+)\/edit/i', $body, $matches);
      if (count($matches[1]) === 1) {
        return intval($matches[1][0]);
      }

      $crawler = new Crawler($body);
      $shortlink = $crawler->filter('link[rel=shortlink]')->getNode(0);
      if ($shortlink) {
        $user_uri = $shortlink->getAttribute('href');
      }
      if ($user_uri && preg_match('/user\/(\d+)/', $user_uri, $matches)) {
        return intval($matches[1]);
      }
    }
    catch (ConnectException $exception) {
      return 0;
    }

    return 0;
  }

  /**
   * Perform one or more requests to obtain the user email.
   *
   * @param \AKlump\CheckPages\DataStructure\UserInterface $user
   *
   * @return string
   *   The users email if available.
   *
   * @see \AKlump\CheckPages\Helpers\AuthenticateDrupal::login()
   */
  protected function requestUserEmail(UserInterface $user): string {
    $uid = $user->id();
    if (!$uid) {
      return '';
    }

    $url = Url::fromString($this->loginUrl);
    $url = (string) $url->withPath('/user/' . $uid . '/edit');
    $cookie_header = $this->getSession()->getCookieHeader();
    try {
      $log_file_contents = HttpLogging::request('Request the user email', 'get', $url, [
        'Cookie' => $cookie_header,
      ]);
      $this->writeLogFile($log_file_contents);
      $response = $this->httpClient
        ->setWhyForNextRequestOnly(__METHOD__)
        ->sendRequest(new Request('get', $url, [
          'Cookie' => $cookie_header,
        ]));
      if ($response->getStatusCode() === 200) {
        $body = (string) $response->getBody();
        $crawler = new Crawler($body);
        $email_input = $crawler->filter('input[name="mail"]')->getNode(0);
        if ($email_input) {
          $mail = $email_input->getAttribute('value');
        }
      }
    }
    catch (ConnectException $exception) {
      $mail = '';
    }

    return $mail ?? '';
  }

  public function getSession(): SessionInterface {
    return $this->session;
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionExpires(): int {
    @trigger_error(sprintf('%s() is deprecated in version 23.3 and is removed from . Use AuthenticateInterface::getSession() instead.', __METHOD__), E_USER_DEPRECATED);

    return $this->getSession()->getExpires();
  }

  /**
   * @inherit
   */
  public function getSessionCookie(): string {
    @trigger_error(sprintf('%s() is deprecated in version 23.3 and is removed from . Use AuthenticateInterface::getSession() instead.', __METHOD__), E_USER_DEPRECATED);
    $header = $this->getSession()->getCookieHeader();
    if (!$header) {
      throw new RuntimeException('Missing session, have you called ::login()?');
    }

    return $header;
  }

  /**
   * @return \GuzzleHttp\Psr7\Response
   *   The most recent response instance.
   */
  public function getResponse(): Response {
    return $this->response;
  }

  /**
   * {@inheritdoc}
   */
  public function getCsrfToken(): string {
    if (!isset($this->csrfToken)) {
      $url = Url::fromString($this->loginUrl);
      $url = (string) $url->withPath('/session/token');
      try {
        $this->csrfToken = (string) $this->httpClient
          ->setWhyForNextRequestOnly(__METHOD__)
          ->sendRequest(new Request('get', $url, [
            'Cookie' => $this->getSession()->getCookieHeader(),
          ]))
          ->getBody();
      }
      catch (ConnectException $exception) {
        return '';
      }
    }

    return $this->csrfToken;
  }

  protected function establishUserSession(UserInterface $user): void {
    $login_url = Url::fromString($this->loginUrl);
    $login_url = (string) $login_url->withQueryParameter('_format', 'json');
    $data = [
      'name' => $user->getAccountName(),
      'pass' => $user->getPassword(),
    ];
    $method = 'post';
    $log_file_contents = HttpLogging::request('Authenticate the user', $method, $login_url, [], $data);
    $this->writeLogFile($log_file_contents);

    $guzzle = new GuzzleDriver();
    $jar = new CookieJar();
    try {
      $this->response = $guzzle
        ->getClient()
        ->request($method, $login_url, [
          'cookies' => $jar,
          'json' => $data,
        ]);
    }
    catch (GuzzleException $e) {
      throw new RuntimeException(sprintf('Failed to establish session for username "%s" at: %s.', $user->getAccountName(), $login_url), 0, $e);
    }

    $session_cookie = array_values(array_filter($jar->toArray(), function ($item) {
      return strpos($item['Name'], 'SESS') !== FALSE;
    }))[0] ?? NULL;

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

    // We may have some additional information that we can save on future
    // requests by gleening it from this response.
    $json = strval($this->response->getBody());
    $data = json_decode($json, TRUE);
    if (isset($data['current_user']['uid'])) {
      $user->setId($data['current_user']['uid']);
    }
    if (isset($data['csrf_token'])) {
      $this->csrfToken = $data['csrf_token'];
    }
  }
}
