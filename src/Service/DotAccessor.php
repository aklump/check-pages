<?php

namespace AKlump\CheckPages\Service;

use Dflydev\DotAccessData\Data;

class DotAccessor {

  /**
   * @var mixed
   */
  private $data;

  public function __construct($data) {
    if ($data instanceof \stdClass) {
      $data = json_decode(json_encode($data), TRUE);
    }
    $this->data = $data;
  }

  public function get(string $path) {
    // Allow '' $path to return the entire $data, IOW, '' is the root selector.
    if ('' === $path) {
      return $this->data;
    }
    elseif (!is_array($this->data)) {
      return NULL;
    }
    $data = new Data($this->data);

    return !$data->has($path) ? NULL : $data->get($path);
  }

  public function has(string $path): bool {
    // Allow '' $path to return the entire $data.
    if ('' === $path && !empty($this->data)) {
      return TRUE;
    }
    elseif (!is_array($this->data)) {
      return FALSE;
    }

    return (new Data($this->data))->has($path);
  }
}
