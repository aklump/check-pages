<?php

namespace AKlump\CheckPages\Files;

class PathDoesNotExistException extends \InvalidArgumentException {

  public function __construct(string $path) {
    $message = sprintf('The path "%s" does not exist.', $path);

    return parent::__construct($message);
  }

}
