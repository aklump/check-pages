<?php

namespace AKlump\CheckPages\DataStructure;

interface UserInterface {

  public function setProperty(string $name, $value);

  public function getProperty($name);

  /**
   * Returns the unaltered login name of this account.
   *
   * @return string
   *   An unsanitized plain-text string with the name of this account that is
   *   used to log in. Only display this name to admins and to the user who owns
   *   this account, and only in the context of the name used to login. For
   *   any other display purposes, use
   *   \Drupal\Core\Session\AccountInterface::getDisplayName() instead.
   */
  public function getAccountName(): string;

  /**
   * Returns the email address of this account.
   *
   * @return string|null
   *   The email address, or NULL if the account is anonymous or the user does
   *   not have an email address.
   */
  public function getEmail(): ?string;

  /**
   * Returns the user ID or 0 for anonymous.
   *
   * @return int
   *   The user ID.
   */
  public function id(): int;

  /**
   * Get the user password.
   *
   * @return string
   *   The plain-text user password.
   */
  public function getPassword(): string;
}
