<?php

namespace AKlump\CheckPages\Options;

class User implements UserInterface, \JsonSerializable {

  protected $name;

  protected $mail;

  protected $uid;

  protected $pass;

  /**
   * @param mixed $name
   *
   * @return
   *   Self for chaining.
   */
  public function setAccountName($name): self {
    $this->name = $name;

    return $this;
  }

  /**
   * @param mixed $mail
   *
   * @return
   *   Self for chaining.
   */
  public function setEmail($mail): self {
    $this->mail = $mail;

    return $this;
  }

  /**
   * @param mixed $uid
   *
   * @return
   *   Self for chaining.
   */
  public function setId($uid): self {
    $this->uid = $uid;

    return $this;
  }

  /**
   * @param mixed $pass
   *
   * @return
   *   Self for chaining.
   */
  public function setPassword($pass): self {
    $this->pass = $pass;

    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getAccountName() {
    return $this->name;
  }

  /**
   * @inheritDoc
   */
  public function getEmail() {
    return $this->mail;
  }

  /**
   * @inheritDoc
   */
  public function id() {
    return $this->uid;
  }

  public function getPassword(): string {
    return $this->pass;
  }

  public function jsonSerialize() {
    return [
      'uid' => $this->id(),
      'name' => $this->getAccountName(),
      'pass' => $this->getPassword(),
      'mail' => $this->getEmail(),
    ];
  }
}
