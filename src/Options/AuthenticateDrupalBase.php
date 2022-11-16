<?php

namespace AKlump\CheckPages\Options;

use AKlump\CheckPages\GuzzleDriver;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Yaml\Yaml;

/**
 * Base class for Drupal Authentication.
 */
abstract class AuthenticateDrupalBase implements AuthenticationInterface {

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
  public function __construct(string $path_to_users_login_data, string $absolute_login_url, string $form_selector, string $form_id) {
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
    $user_data = array_first(array_filter($users, function ($account) use ($username) {
      return $account['name'] === $username;
    }));
    if (empty($user_data)) {
      throw new \RuntimeException(sprintf('No record for user "%s" in %s', $username, $this->usersFile));
    }
    if (empty($user_data['pass'])) {
      throw new \RuntimeException(sprintf('Missing "pass" key for the user "%s" in %s', $username, $this->usersFile));
    }
    $user = new User();

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
    $authenticator = new GuzzleDriver();
    $this->response = $authenticator->getClient()->get($this->loginUrl);
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
      $failed_message = [sprintf('Login failed for username "%s".', $username)];
      $failed = FALSE;
      $this->response = $authenticator
        ->getClient()
        ->request('POST', $this->loginUrl, [
          'cookies' => $jar,
          'form_params' => [
            'op' => 'Log in',
            'name' => $username,
            'pass' => $password,
            'form_id' => $this->formId,
            'form_build_id' => $form_build_id,
          ],
        ]);
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

    $session_cookie = array_first(array_filter($jar->toArray(), function ($item) {
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

  /**
   * Perform one or more requests to obtain the user ID.
   *
   * @param \AKlump\CheckPages\Options\UserInterface $user
   *
   * @return int
   *   The User ID if it can be deteremine.
   *
   * @see \AKlump\CheckPages\Options\AuthenticateDrupalBase::login()
   */
  protected function requestUserId(UserInterface $user): int {
    $parts = parse_url($this->loginUrl);
    $url = $parts['scheme'] . '://' . $parts['host'] . '/user';
    try {
      $guzzle = new GuzzleDriver();
      $response = $guzzle
        ->setUrl($url)
        ->setHeader('Cookie', $this->getSessionCookie())
        ->request();
      $location = $response->getLocation();
      // If the user page is not aliased the UID will appear in the location bar.
      if (preg_match('/user\/(\d+)/', $location, $matches)) {
        return intval($matches[1]);
      }
      $body = strval($response->getResponse()->getBody());
      $crawler = new Crawler($body);

      // Sometimes it appears in a <link> tag.
      $shortlink = $crawler->filter('link[rel=shortlink]')->getNode(0);
      if ($shortlink) {
        $shortlink = $shortlink->getAttribute('href');
      }
      if ($shortlink && preg_match('/user\/(\d+)/', $shortlink, $matches)) {
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
   * @param \AKlump\CheckPages\Options\UserInterface $user
   *
   * @return string
   *   The users email if available.
   *
   * @see \AKlump\CheckPages\Options\AuthenticateDrupalBase::login()
   */
  protected function requestUserEmail(UserInterface $user): string {
    $uid = $user->id();
    if (!$uid) {
      return '';
    }
    $parts = parse_url($this->loginUrl);
    $url = $parts['scheme'] . '://' . $parts['host'] . "/user/$uid/edit";
    try {
      $guzzle = new GuzzleDriver();
      $response = $guzzle
        ->setUrl($url)
        ->setHeader('Cookie', $this->getSessionCookie())
        ->request()
        ->getResponse();
      $body = strval($response->getBody());
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
  public function getResponse(): \GuzzleHttp\Psr7\Response {
    return $this->response;
  }

}
