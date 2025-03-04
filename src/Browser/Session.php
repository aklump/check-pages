<?php

namespace AKlump\CheckPages\Browser;

use AKlump\CheckPages\DataStructure\User;
use AKlump\CheckPages\DataStructure\UserInterface;

class Session implements SessionInterface {

  protected User $user;

  protected string $name;

  public function getName(): string {
    return $this->name;
  }

  public function setName(string $name): void {
    $this->name = $name;
  }

  public function getValue(): string {
    return $this->value;
  }

  public function setValue(string $value): void {
    $this->value = $value;
  }

  protected string $value;

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
