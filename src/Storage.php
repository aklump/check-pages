<?php

namespace AKlump\CheckPages;

use AKlump\CheckPages\Files\FilesProviderInterface;

/**
 * Storage of runner data.
 */
class Storage implements StorageInterface {

  /**
   * Path to the file for persistent storage.
   *
   * @var string
   */
  protected $filepath;

  private $data;

  public function __construct(FilesProviderInterface $log_files) {
    $this->filepath = $log_files->tryResolveFile('cache/storage.json', [], FilesProviderInterface::RESOLVE_NON_EXISTENT_PATHS)[0];
    $log_files->tryCreateDir(dirname($this->filepath));
    if (file_exists($this->filepath)) {
      $data = json_decode(file_get_contents($this->filepath), TRUE);
    }
    $this->data = isset($data) && is_array($data) ? $data : [];
  }

  /**
   * Store contents to disk if storage location is known.
   */
  public function __destruct() {
    if (!empty($this->filepath) && is_dir(dirname($this->filepath))) {
      $data = json_encode($this->data);
      if (FALSE !== $data) {
        file_put_contents($this->filepath, $data);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($key, $default = NULL) {
    return $this->data[$key] ?? $default;
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    $this->data[$key] = $value;
  }

}
