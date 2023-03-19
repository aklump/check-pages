<?php

namespace AKlump\CheckPages\Files;

class NotInResolvableDirectoryException extends \InvalidArgumentException {

  public function __construct(string $path) {
    $message = sprintf('The path "%s" is outside of all resolvable directories; or does not exist.', $path);

    return parent::__construct($message);
  }

}
