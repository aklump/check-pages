<?php

namespace AKlump\CheckPages\Options;

use AKlump\CheckPages\GuzzleDriver;
use GuzzleHttp\Cookie\CookieJar;
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

    // Log in and get a session.
    $jar = new CookieJar();
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

    // Look for the login form again in the page response, if it's still here,
    // that means login failed.  We could also look in the headers for
    // "Set-Cookie" to see if it changed from the request above, if this doesn't
    // prove to work over time.  A changed session cookie indicates a new
    // session was created, but not necessarily that the login succeeded.
    $crawler = new Crawler(strval($this->response->getBody()));
    if ($crawler->filter($this->formSelector)->count() > 0) {
      throw new \RuntimeException(sprintf('Login failed for username "%s".', $username));
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
