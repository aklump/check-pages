<?php

namespace AKlump\CheckPages\Helpers;

/**
 * Interface AuthenticationInterface.
 *
 * @package AKlump\CheckPages\Options
 */
interface AuthenticationInterface {

  /**
   * Login a user.
   *
   * @param \AKlump\CheckPages\Helpers\UserInterface $user
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
