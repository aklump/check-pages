<?php

namespace AKlump\CheckPages\Files;

class NotWriteableException extends \InvalidArgumentException {

  public function __construct(string $path) {
    $message = sprintf('The path "%s" is not writeable.', $path);

    return parent::__construct($message);
  }

}
