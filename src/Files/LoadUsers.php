<?php

namespace AKlump\CheckPages\Files;

use AKlump\CheckPages\DataStructure\User;
use InvalidArgumentException;
use Symfony\Component\Yaml\Yaml;

/**
 * Load user list from data file.
 *
 * @see \AKlump\CheckPages\Helpers\FilterUsersByName
 */
class LoadUsers {

  /**
   * @param string $users_file Absolute path to a YAML or JSON file containing
   * user credentials.  It should contain an array of arrays, each with a duplet
   * of keys [(user|name|username),(pass|password)].
   *
   * @return \AKlump\CheckPages\DataStructure\User[]
   *
   * @throws \InvalidArgumentException If $users_file does not exist.
   */
  public function __invoke(string $users_file): array {
    if (!file_exists($users_file)) {
      throw new InvalidArgumentException(sprintf('Users file "%s" does not exist.', $users_file));
    }
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
      $name_key = NULL;
      $pass_key = NULL;
      $user = new User(
        $this->getUsernameFromData($user_data, $pass_key),
        $this->getPasswordFromData($user_data, $name_key),
      );
      foreach ($user_data as $name => $value) {
        if (in_array($name, [$name_key, $pass_key])) {
          continue;
        }
        $user->setProperty($name, $value);
      }

      return $user;
    }, $users_data);
  }

  private function getPasswordFromData($user_data, &$key): string {
    if (isset($user_data['pass'])) {
      return $user_data['pass'];
    }
    elseif (isset($user_data['password'])) {
      return $user_data['password'];
    }

    return '';
  }

  private function getUsernameFromData($user_data, &$key): string {
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
