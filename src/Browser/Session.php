<?php

namespace AKlump\CheckPages\Browser;

use AKlump\CheckPages\DataStructure\User;
use AKlump\CheckPages\DataStructure\UserInterface;

final class Session implements SessionInterface {

  private User $user;

  private string $name = '';

  private string $value = '';

  private int $expires;

  public function getName(): string {
    return $this->name;
  }

  public function setName(string $session_name): void {
    $this->name = $session_name;
  }

  public function getValue(): string {
    return $this->value;
  }

  public function setValue(string $session_value): void {
    $this->value = $session_value;
  }

  public function getUser(): UserInterface {
    return $this->user;
  }

  public function setUser(UserInterface $user): void {
    $this->user = $user;
  }

  public function getSessionCookie(): string {
    @trigger_error(sprintf('%s() is deprecated in version 0.23.3 and is removed from . Use SessionInterface::getCookieHeader() instead.', __METHOD__), E_USER_DEPRECATED);

    return $this->getCookieHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function getCookieHeader(): string {
    if (!$this->getName() || !$this->getValue()) {
      return '';
    }

    return implode('=', [$this->getName(), $this->getValue()]);
  }

  public function setExpires(int $expiration) {
    $this->expires = $expiration;
  }

  public function getExpires(): int {
    return $this->expires;
  }
}
