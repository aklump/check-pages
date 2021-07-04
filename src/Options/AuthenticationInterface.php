<?php

namespace AKlump\CheckPages\Options;

/**
 * Interface AuthenticationInterface.
 *
 * @package AKlump\CheckPages\Options
 */
interface AuthenticationInterface {

  /**
   * Return data about a user.
   *
   * @param string $username
   *   The username to lookup.
   *
   * @return array
   *   At least the keys: name and pass.
   */
  public function getUser(string $username): array;

  /**
   * Login a user.
   *
   * @param string $username
   *   The username to use.
   * @param string $password
   *   The password to use.
   *
   * @throws \RuntimeException
   *   If the login failed.
   */
  public function login(string $username, string $password);

  /**
   * Get the value to send in the "Cookie" header for the authenticated session.
   *
   * @return string
   *   The value of the authenticated session cookie from login.
   *
   * @throws \RuntimeException
   *   The there is no session, i.e., ::login has not first been called.
   */
  public function getSessionCookie(): string;

}
