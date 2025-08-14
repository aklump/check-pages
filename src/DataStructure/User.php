<?php

namespace AKlump\CheckPages\DataStructure;

use DateTimeZone;
use JsonSerializable;

class User implements UserInterface, JsonSerializable {

  protected string $name = '';

  protected string $mail = '';

  protected int $uid = 0;

  protected string $pass = '';

  protected DateTimeZone $timeZone;

  protected array $properties = [];

  /**
   * @param string $name
   * @param string|null $pass
   */
  public function __construct(string $name = '', string $pass = '') {
    $this->name = $name;
    $this->pass = $pass;
    $this->setTimeZone(new DateTimeZone('UTC'));
  }


  /**
   * @param mixed $name
   *
   * @return \AKlump\CheckPages\DataStructure\UserInterface
   *   Self for chaining.
   */
  public function setAccountName($name): UserInterface {
    $this->name = $name;

    return $this;
  }

  /**
   * @param mixed $mail
   *
   * @return \AKlump\CheckPages\DataStructure\UserInterface
   *   Self for chaining.
   */
  public function setEmail($mail): UserInterface {
    $this->mail = $mail;

    return $this;
  }

  /**
   * @param mixed $uid
   *
   * @return \AKlump\CheckPages\DataStructure\UserInterface
   *   Self for chaining.
   */
  public function setId($uid): UserInterface {
    $this->uid = $uid;

    return $this;
  }

  /**
   * @param mixed $pass
   *
   * @return \AKlump\CheckPages\DataStructure\UserInterface
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
    return $this->uid ?? 0;
  }

  public function getPassword(): string {
    return $this->pass;
  }

  public function getTimeZone(): DateTimeZone {
    return $this->timeZone;
  }

  public function setTimeZone(DateTimeZone $time_zone): void {
    $this->timeZone = $time_zone;
  }

  public function jsonSerialize(): array {
    return [
      'uid' => $this->id(),
      'name' => $this->getAccountName(),
      'pass' => $this->getPassword(),
      'mail' => $this->getEmail(),
      '_props' => $this->properties,
    ];
  }

  public function setProperty(string $name, $value) {
    $this->properties[$name] = $value;
  }

  public function getProperty($name) {
    return $this->properties[$name] ?? NULL;
  }
}
