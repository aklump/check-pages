<?php

namespace AKlump\CheckPages\Traits;

trait HasFilePathTrait {

  private string $filePath;

  public function getFilePath(): string {
    return $this->filePath;
  }

  /**
   * @return $this
   */
  public function setFilePath(string $filePath) {
    $this->filePath = $filePath;

    return $this;
  }

}
