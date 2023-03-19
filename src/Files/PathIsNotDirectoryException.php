<?php

namespace AKlump\CheckPages\Files;

class PathIsNotDirectoryException extends \InvalidArgumentException {

  public function __construct(string $path) {
    $message = sprintf('The path "%s" is not an existing directory.', $path);

    return parent::__construct($message);
  }

}
