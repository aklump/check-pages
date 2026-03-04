<?php

namespace AKlump\CheckPages\Output\Message;

use AKlump\CheckPages\Exceptions\StopRunnerException;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\Messaging\MessageType;
use Exception;

/**
 * Represents an error message generated from exceptions.
 * Extends the base Message class to handle specific exception-related logic.
 *
 * The message verbosity will be determined by the type of exception class.
 */
class ExceptionMessage extends Message {

  const SEARCH_DEPTH = 4;

  public function __construct(Exception $exception, int $verbosity = Verbosity::NORMAL, string $fallback_message = '') {
    $message = $this->lookDeepForMessage($exception) ?: trim($fallback_message);
    $verbosity = $verbosity ?? $this->getVerbosityByException($exception);

    // If the message and fallback are both empty then include the trace.
    if (empty($message) || $verbosity & Verbosity::VERBOSE) {
      $message .= ($message ? PHP_EOL : '') . $exception->getTraceAsString();
    }

    parent::__construct(
      explode(PHP_EOL, $message),
      $this->getMessageTypeByException($exception),
      $this->getVerbosityByException($exception)
    );
  }

  private function lookDeepForMessage(\Throwable $value, array &$context = []) {
    $context += ['count' => 0];
    if ($context['count']++ > static::SEARCH_DEPTH) {
      return '';
    }
    $message = trim($value->getMessage());
    if ($message) {
      return $message;
    }
    $previous = $value->getPrevious();
    if (!$previous instanceof \Throwable) {
      return '';
    }

    return $this->lookDeepForMessage($previous, $context);
  }

  private function getMessageTypeByException(Exception $exception): string {
    if ($exception instanceof StopRunnerException) {
      // This is a major error and must be seen at all times.
      return MessageType::EMERGENCY;
    }

    return MessageType::ERROR;
  }

  private function getVerbosityByException(Exception $exception): int {
    if ($exception instanceof StopRunnerException) {
      // This is a major error and must be seen at all times.
      return Verbosity::NORMAL;
    }

    return Verbosity::NORMAL;
  }

}
