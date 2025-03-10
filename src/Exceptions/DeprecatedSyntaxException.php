<?php

namespace AKlump\CheckPages\Exceptions;

use LogicException;

/**
 * Class OutdatedSyntaxException
 *
 * Thrown when test syntax provided in configuration is incorrect, invalid, or deprecated.
 * Suggests the correct syntax to the user.
 *
 * @package AKlump\CheckPages\Exceptions
 */
class DeprecatedSyntaxException extends LogicException {

  const PREFIX = 'Deprecated Syntax: ';

  /**
   * @param string $outdated_syntax e.g. 'form.input.option'
   * @param string $current_syntax e.g, 'form.input.value'
   */
  public function __construct(string $outdated_syntax, string $current_syntax) {
    $message = sprintf('Change "%s" to "%s".', $outdated_syntax, $current_syntax);

    return parent::__construct(sprintf('%s%s', self::PREFIX, $message));
  }
}
