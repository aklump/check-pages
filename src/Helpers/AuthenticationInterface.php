<?php

namespace AKlump\CheckPages\Helpers;

use AKlump\CheckPages\Browser\SessionInterface;
use AKlump\CheckPages\DataStructure\UserInterface;

/**
 * Interface AuthenticationInterface.
 *
 * @package AKlump\CheckPages\Options
 */
interface AuthenticationInterface {

  /**
   * Login a user.
   *
   * @param \AKlump\CheckPages\DataStructure\UserInterface $user
   *
   * @throws \RuntimeException
   *   If the login failed.
   */
  public function login(UserInterface $user);

  public function getSession(): SessionInterface;

  /**
   * Get the value to send in the "Cookie" header for the authenticated session.
   *
   * @return string
   *   The value of the authenticated session cookie from login.
   *
   * @throws \RuntimeException
   *   The there is no session, i.e., ::login has not first been called.
   *
   * @deprecated Since version 23.3, Use AuthenticateInterface::getSession() instead.
   */
  public function getSessionCookie(): string;

  /**
   * Get cookie expiry.
   *
   * @return int
   *   The timestamp for when the cookie expires.
   *
   * @deprecated Since version 23.3, Use AuthenticateInterface::getSession() instead.
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
