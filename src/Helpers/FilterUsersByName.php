<?php

namespace AKlump\CheckPages\Helpers;

use AKlump\CheckPages\DataStructure\User;

class FilterUsersByName {

  private array $users;

  /**
   * @param array $users
   *
   * @see \AKlump\CheckPages\Files\LoadUsers
   */
  public function __construct(array $users) {
    $this->users = $users;
  }

  public function __invoke(string $username): ?User {
    return array_values(array_filter($this->users, function ($u) use ($username) {
      return $u->getAccountName() === $username;
    }))[0] ?? NULL;
  }
}
