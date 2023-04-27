<?php

namespace AKlump\CheckPages\Helpers;

use JsonSerializable;

class User implements UserInterface, JsonSerializable {

  protected $name;

  protected $mail;

  protected $uid;

  protected $pass;

  /**
   * @param mixed $name
   *
   * @return \AKlump\CheckPages\Helpers\UserInterface
   *   Self for chaining.
   */
  public function setAccountName($name): UserInterface {
    $this->name = $name;

    return $this;
  }

  /**
   * @param mixed $mail
   *
   * @return \AKlump\CheckPages\Helpers\UserInterface
   *   Self for chaining.
   */
  public function setEmail($mail): UserInterface {
    $this->mail = $mail;

    return $this;
  }

  /**
   * @param mixed $uid
   *
   * @return \AKlump\CheckPages\Helpers\UserInterface
   *   Self for chaining.
   */
  public function setId($uid): UserInterface {
    $this->uid = $uid;

    return $this;
  }

  /**
   * @param mixed $pass
   *
   * @return \AKlump\CheckPages\Helpers\UserInterface
   *   Self for chaining.
   */
  public function setPassword($pass): UserInterface {
    $this->pass = $pass;

    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getAccountName(): string {
    return $this->name;
  }

  /**
   * @inheritDoc
   */
  public function getEmail(): ?string {
    return $this->mail;
  }

  /**
   * @inheritDoc
   */
  public function id(): int {
    return $this->uid;
  }

  public function getPassword(): string {
    return $this->pass;
  }

  public function jsonSerialize(): array {
    return [
      'uid' => $this->id(),
      'name' => $this->getAccountName(),
      'pass' => $this->getPassword(),
      'mail' => $this->getEmail(),
    ];
  }
}
