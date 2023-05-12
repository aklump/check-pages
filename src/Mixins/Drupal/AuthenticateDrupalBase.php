<?php

namespace AKlump\CheckPages\Mixins\Drupal;

use AKlump\CheckPages\Browser\GuzzleDriver;
use AKlump\CheckPages\Files\FilesProviderInterface;
use AKlump\CheckPages\Files\HttpLogging;
use AKlump\CheckPages\Helpers\AuthenticationInterface;
use AKlump\CheckPages\Helpers\UserInterface;
use AKlump\CheckPages\HttpClient;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Yaml\Yaml;

/**
 * Base class for Drupal Authentication.
 */
abstract class AuthenticateDrupalBase implements AuthenticationInterface {

  const LOG_FILE_PATH = 'drupal/authenticate.http';

  /**
   * @var string
   */
  private $sessionValue;

  /**
   * @var string
   */
  private $sessionName;

  /**
   * @var \DateTime
   */
  private $sessionExpires;

  /**
   * The most recent authentication request response.
   *
   * @var \GuzzleHttp\Psr7\Response
   */
  private $response;

  /**
   * @var \AKlump\CheckPages\Files\FilesProviderInterface
   */
  protected $logFiles;

  /**
   * @var string
   */
  protected $usersFile;

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
   * AuthenticateDrupalBase constructor.
   *
   * @param string $path_to_users_login_data
   *   The resolved path to the JSON or YAML file for the users.
   * @param string $absolute_login_url
   *   The absolute URL to the login form.
   * @param string $form_id
   *   The value of the hidden input name=form_id.
   * @param string $form_selector
   *   A CSS selector to find the login form on in the DOM of the
   *   $absolute_login_url.
   */
  public function __construct(HttpClient $http_client, FilesProviderInterface $log_files, string $path_to_users_login_data, string $absolute_login_url, string $form_selector, string $form_id) {
    $this->httpClient = $http_client;
    $this->logFiles = $log_files;
    $this->usersFile = $path_to_users_login_data;
    $this->loginUrl = $absolute_login_url;
    $this->formId = $form_id;
    $this->formSelector = $form_selector;
  }

  /**
   * @inherit
   */
  public function getUser(string $username): UserInterface {
    // Load our non-version username/password index.
    switch (pathinfo($this->usersFile, PATHINFO_EXTENSION)) {
      case 'yaml':
      case 'yml':
        $users = Yaml::parseFile($this->usersFile);
        break;

      case 'json':
        $users = json_decode(file_get_contents($this->usersFile), TRUE);
        break;
    }

    // Find the account by username.
    $user_data = \array_first(array_filter($users, function ($account) use ($username) {
      return $account['name'] === $username;
    }));
    if (empty($user_data)) {
      throw new \RuntimeException(sprintf('No record for user "%s" in %s', $username, $this->usersFile));
    }
    if (empty($user_data['pass'])) {
      throw new \RuntimeException(sprintf('Missing "pass" key for the user "%s" in %s', $username, $this->usersFile));
    }
    $user = new \AKlump\CheckPages\Helpers\User();

    return $user->setPassword($user_data['pass'])->setAccountName($username);
  }

  /**
   * @param string $username
   * @param string $password
   * @param string $login_url
   * @param string $form_id
   * @param string $form_selector
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
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
    if ($form_build_id) {
      $form_build_id = $form_build_id->getAttribute('value');
    }
    else {
      throw new \RuntimeException(sprintf('Login form missing from %s', $this->loginUrl));
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
      throw new \RuntimeException(implode(PHP_EOL, $failed_message));
    }

    $session_cookie = \array_first(array_filter($jar->toArray(), function ($item) {
      return strpos($item['Name'], 'SESS') !== FALSE;
    }));
    if (empty($session_cookie)) {
      throw new \RuntimeException(sprintf('Did not obtain session cookie for username "%s".', $username));
    }

    $this->sessionName = $session_cookie['Name'];
    $this->sessionValue = $session_cookie['Value'];
    $this->sessionExpires = $session_cookie['Expires'];

    $id = $this->requestUserId($user);
    if ($id) {
      $user->setId($id);
    }

    $mail = $this->requestUserEmail($user);
    if ($mail) {
      $user->setEmail($mail);
    }
  }

  private function writeLogFile(string $contents) {
    $filepath = $this->logFiles->tryResolveFile(self::LOG_FILE_PATH, [], FilesProviderInterface::RESOLVE_NON_EXISTENT_PATHS)[0];
    $this->logFiles->tryCreateDir(dirname($filepath));
    $fp = fopen($filepath, 'a');
    fwrite($fp, $contents . PHP_EOL);
    fclose($fp);
  }

  /**
   * Perform one or more requests to obtain the user ID.
   *
   * @param \AKlump\CheckPages\Helpers\UserInterface $user
   *
   * @return int
   *   The User ID if it can be deteremine.
   *
   * @see \AKlump\CheckPages\Helpers\AuthenticateDrupalBase::login()
   */
  protected function requestUserId(UserInterface $user): int {
    $parts = parse_url($this->loginUrl);
    $url = $parts['scheme'] . '://' . $parts['host'] . '/user';
    try {

      $log_file_contents = HttpLogging::request('Request the user ID', 'get', $url, [
        'Cookie' => $this->getSessionCookie(),
      ]);
      $this->writeLogFile($log_file_contents);

      $this->httpClient
        ->setWhyForNextRequestOnly(__METHOD__)
        ->sendRequest(new Request('get', $url, ['Cookie' => $this->getSessionCookie()]));
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
   * @param \AKlump\CheckPages\Helpers\UserInterface $user
   *
   * @return string
   *   The users email if available.
   *
   * @see \AKlump\CheckPages\Helpers\AuthenticateDrupalBase::login()
   */
  protected function requestUserEmail(UserInterface $user): string {
    $uid = $user->id();
    if (!$uid) {
      return '';
    }
    $parts = parse_url($this->loginUrl);
    $url = $parts['scheme'] . '://' . $parts['host'] . "/user/$uid/edit";
    try {
      $log_file_contents = HttpLogging::request('Request the user email', 'get', $url, [
        'Cookie' => $this->getSessionCookie(),
      ]);
      $this->writeLogFile($log_file_contents);

      $body = (string) $this->httpClient
        ->setWhyForNextRequestOnly(__METHOD__)
        ->sendRequest(new Request('get', $url, [
          'Cookie' => $this->getSessionCookie(),
        ]))->getBody();

      $crawler = new Crawler($body);
      $email_input = $crawler->filter('input[name="mail"]')->getNode(0);
      if ($email_input) {
        $mail = $email_input->getAttribute('value');
      }
    }
    catch (ConnectException $exception) {
      $mail = '';
    }

    return $mail ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionExpires(): int {
    return $this->sessionExpires;
  }

  /**
   * @inherit
   */
  public function getSessionCookie(): string {
    if (empty($this->sessionName) || empty($this->sessionValue)) {
      throw new \RuntimeException('Missing session, have you called ::login()?');
    }

    return $this->sessionName . '=' . $this->sessionValue;
  }

  /**
   * @return \GuzzleHttp\Psr7\Response
   *   The most recent response instance.
   */
  public function getResponse(): Response {
    return $this->response;
  }

}
