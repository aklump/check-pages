<?php

namespace AKlump\CheckPages\Browser;

use AKlump\CheckPages\DataStructure\UserInterface;

interface SessionInterface {

  public function setUser(UserInterface $user);

  public function setName(string $session_name);

  public function setValue(string $session_value);

  /**
   * @param int $expiration The unix timestamp for the session expiration.
   *
   * @return void
   */
  public function setExpires(int $expiration);

  public function getExpires(): int;

  /**
   * Get the cookie header value.
   *
   * @return string The "{name}={value}" concantenation.
   *
   * @deprecated Since version 0.23.3, Use SessionInterface::getCookieHeader() instead.
   */
  public function getSessionCookie(): string;

  /**
   * Get the cookie header value.
   *
   * @return string The "{name}={value}" concantenation.
   */
  public function getCookieHeader(): string;
}
