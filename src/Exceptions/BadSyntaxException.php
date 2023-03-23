<?php

namespace AKlump\CheckPages\Exceptions;

/**
 * Used when a Suite, Test or Assert does not have the correct syntax.
 *
 * This should be thrown during the boostrap of a suite and not after the tests
 * have started.  For example during event responders to the Event::SUITE_STARTED
 * event.
 */
class BadSyntaxException extends \LogicException {

  const PREFIX = 'Syntax Error: ';

  /**
   * @param $message
   *   A message describing the syntax error.
   * @param $context_object
   *   An object, e.g. Suite or Test which best describes the scope of the message.
   *
   * @throws \ReflectionException
   */
  public function __construct($message, $context_object = NULL) {
    $prefix = BadSyntaxException::PREFIX;
    $config = '';
    if ($context_object) {
      $prefix = new \ReflectionClass($context_object);
      $prefix = $prefix->getShortName() . ' ' . BadSyntaxException::PREFIX;
      if (method_exists($context_object, 'getConfig')) {
        $config = "\n" . json_encode($context_object->getConfig(), JSON_PRETTY_PRINT);
      }
    }
    parent::__construct(sprintf('%s%s%s', $prefix, $message, $config));
  }
}
