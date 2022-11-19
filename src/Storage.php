<?php

namespace AKlump\CheckPages;

use Adam\Bag\Bag;

/**
 * Storage of runner data.
 */
class Storage implements StorageInterface {

  /**
   * Path to the file for persistent storage.
   *
   * @var string
   */
  protected $diskStorage;

  private $dataset = [];

  /**
   * Storage constructor.
   *
   * @param string $path_to_disk_storage
   *   A fullpath to the JSON file where the persistent storage should be a
   *   saved.  If it's directory does not exist then the data will not be saved.
   */
  public function __construct($path_to_disk_storage) {
    if (!empty($path_to_disk_storage)) {
      $this->diskStorage = dirname($path_to_disk_storage) . '/' . pathinfo($path_to_disk_storage, PATHINFO_FILENAME) . '.json';
      if (file_exists($this->diskStorage)) {
        $this->dataset = json_decode(file_get_contents($this->diskStorage), TRUE);
        if (!is_array($this->dataset)) {
          $this->dataset = [];
        }
      }
    }
  }

  /**
   * Store contents to disk if storage location is known.
   */
  public function __destruct() {
    if (!empty($this->diskStorage) && is_dir(dirname($this->diskStorage))) {
      $data = json_encode($this->dataset);
      if (FALSE !== $data) {
        file_put_contents($this->diskStorage, $data);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($key, $default = NULL) {
    return $this->dataset[$key] ?? $default;
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    $this->dataset[$key] = $value;
  }

}
