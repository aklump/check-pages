<?php

namespace AKlump\CheckPages\Browser;

use AKlump\CheckPages\DataStructure\UserInterface;

interface SessionInterface {

  public function setUser(UserInterface $user);

  public function setName(string $session_name);

  public function setValue(string $session_value);

  /**
   * Get the cookie header value.
   *
   * @return string The "{name}={value}" concentenation.
   */
  public function getSessionCookie(): string;
}
