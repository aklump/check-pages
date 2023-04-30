<?php

namespace AKlump\CheckPages\Traits;


trait HasConfigTrait {

  private $hasConfigTraitConfig;

  /**
   * @return array
   *
   * @see \AKlump\CheckPages\Traits\HasConfigTrait::get()
   * @see \AKlump\CheckPages\Traits\HasConfigTrait::has()
   */
  public function getConfig(): array {
    return $this->hasConfigTraitConfig ?? [];
  }

  /**
   * @param array $config
   *
   * @return self
   *   Self for chaining.
   */
  public function setConfig(array $config): void {
    $this->hasConfigTraitConfig = $config;
  }

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
  public function get(string $config_key) {
    return $this->getConfig()[$config_key] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function set(string $config_key, $value): void {
    $this->setConfig([$config_key => $value] + $this->getConfig());
  }

  /**
   * Check if a configuration key exists.
   *
   * @param string $config_key
   *   A config key to check for.
   *
   * @return bool
   *   True if the configuration has that key.
   */
  public function has(string $config_key): bool {
    return array_key_exists($config_key, $this->getConfig());
  }

}
