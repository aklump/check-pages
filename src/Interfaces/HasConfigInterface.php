<?php

namespace AKlump\CheckPages\Interfaces;

use AKlump\CheckPages\Parts\Test;

interface HasConfigInterface {

  /**
   * @return array
   *
   * @see \AKlump\CheckPages\Traits\HasConfigTrait::get()
   * @see \AKlump\CheckPages\Traits\HasConfigTrait::has()
   */
  public function getConfig(): array;

  /**
   * Get the value of a configuration key
   *
   * @param string $config_key
   *
   * @return mixed|null
   *   Null if the key is set to NULL, or does not exit.
   *
   * @see \AKlump\CheckPages\Traits\HasConfigTrait::get()
   */
  public function get(string $config_key);

  /**
   * Check if a configuration key exists.
   *
   * @param string $config_key
   *   A config key to check for.
   *
   * @return bool
   *   True if the configuration has that key.
   */
  public function has(string $config_key): bool;

  public function setConfig(array $config): void;
}
