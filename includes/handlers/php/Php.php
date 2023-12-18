<?php

namespace AKlump\CheckPages\Handlers;

use AKlump\CheckPages\Traits\SetTrait;

/**
 * Implements the Bash handler.
 */
final class PHP extends CommandLineTestHandlerBase {

  use SetTrait;

  /**
   * {@inheritdoc}
   */
  public static function getId(): string {
    return 'php';
  }

  protected function prepareCommandForCLI(string $command): string {
    $command = addcslashes($command, '"');
    return "php -r \"$command;\"";
  }
}
