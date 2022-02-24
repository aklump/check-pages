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
  public function getUser(string $username): UserInterface;

  /**
   * Login a user.
   *
   * @param \AKlump\CheckPages\Options\UserInterface $user
   *
   * @throws \RuntimeException
   *   If the login failed.
   */
  public function login(UserInterface $user);

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

  /**
   * Get cookie expiry.
   *
   * @return int
   *   The timestamp for when the cookie expires.
   */
  public function getSessionExpires(): int;

  /**
   * Get CSRF token.
   *
   * @return string
   *   A CSRF token for the authenticated user, if available.
   */
  public function getCsrfToken(): string;
}
