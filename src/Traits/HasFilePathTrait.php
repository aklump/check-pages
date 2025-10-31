<?php

namespace AKlump\CheckPages\Traits;

trait HasFilePathTrait {

  private string $filePath;

  public function getFilePath(): string {
    return $this->filePath;
  }

  public function setFilePath(string $filePath): self {
    $this->filePath = $filePath;

    return $this;
  }

}
