<?php

namespace AKlump\CheckPages\Helpers;

use Symfony\Component\Yaml\Yaml;

/**
 * Load user list from data file.
 */
class LoadUsers {

  /**
   * @param string $users_file Absolute path to a YAML or JSON file containing
   * user credentials.  It should contain an array of arrays, each with a duplet
   * of keys [(user|name|username),(pass|password)].
   *
   * @return \AKlump\CheckPages\Helpers\User[]
   */
  public function __invoke(string $users_file): array {
    // Load our non-version username/password index.
    switch (pathinfo($users_file, PATHINFO_EXTENSION)) {
      case 'yaml':
      case 'yml':
        $users_data = Yaml::parseFile($users_file);
        break;

      case 'json':
        $users_data = json_decode(file_get_contents($users_file), TRUE);
        break;
    }
    if (empty($users_data)) {
      return [];
    }

    return array_map(function ($user_data) {
      $password = $this->getPasswordFromData($user_data);
      $username = $this->getUsernameFromData($user_data);

      return (new User())
        ->setAccountName($username)
        ->setPassword($password);
    }, $users_data);
  }

  private function getPasswordFromData($user_data): string {
    if (isset($user_data['pass'])) {
      return $user_data['pass'];
    }
    elseif (isset($user_data['password'])) {
      return $user_data['password'];
    }

    return '';
  }

  private function getUsernameFromData($user_data): string {
    if (isset($user_data['name'])) {
      return $user_data['name'];
    }
    elseif (isset($user_data['user'])) {
      return $user_data['user'];
    }
    elseif (isset($user_data['username'])) {
      return $user_data['username'];
    }

    return '';
  }
}
