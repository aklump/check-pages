<?php

namespace AKlump\CheckPages\Browser;

use AKlump\CheckPages\DataStructure\User;
use AKlump\CheckPages\DataStructure\UserInterface;

class Session implements SessionInterface {

  protected User $user;

  protected string $name = '';

  protected string $value = '';

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
    if (!$this->getName() || !$this->getValue()) {
      return '';
    }

    return implode('=', [$this->getName(), $this->getValue()]);
  }

}
