<?php

namespace AKlump\CheckPages;

use Adam\Bag\Bag;

/**
 * Storage of runner data.
 */
class Storage extends Bag implements StorageInterface {

  /**
   * Storage constructor.
   *
   * @param string $path_to_disk_storage
   *   A fullpath to the JSON file where the persistent storage should be a
   *   saved.  If it's directory does not exist then the data will not be saved.
   */
  public function __construct($path_to_disk_storage) {
    $attributes = [];
    $this->diskStorage = dirname($path_to_disk_storage) . '/' . pathinfo($path_to_disk_storage, PATHINFO_FILENAME) . '.json';
    if (file_exists($this->diskStorage)) {
      $attributes = json_decode(file_get_contents($this->diskStorage), TRUE);
      if (!is_array($attributes)) {
        $attributes = [];
      }
    }
    parent::__construct($attributes);
  }

  /**
   * Store contents to disk if storage location is known.
   */
  public function __destruct() {
    if ($this->all() && is_dir(dirname($this->diskStorage))) {
      $data = json_encode($this);
      if (FALSE !== $data) {
        file_put_contents($this->diskStorage, $data);
      }
    }
  }

}
