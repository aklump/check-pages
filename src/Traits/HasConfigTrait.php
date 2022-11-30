<?php

namespace AKlump\CheckPages\Traits;


trait HasConfigTrait {

  private $hasConfigTraitConfig;

  /**
   * @return array
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
  public function setConfig(array $config): self {
    $this->hasConfigTraitConfig = $config;

    return $this;
  }

}
