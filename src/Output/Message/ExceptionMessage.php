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

  public function __construct(Exception $exception) {
    parent::__construct(
      explode(PHP_EOL, $exception->getMessage()),
      $this->getMessageTypeByException($exception),
      $this->getVerbosityByException($exception)
    );
  }

  private function getMessageTypeByException(Exception $exception): string {
    if ($exception instanceof StopRunnerException) {
      // This is a major error and must be seen at all times.
      return MessageType::EMERGENCY;
    }

    return MessageType::ERROR;
  }

  private function getVerbosityByException(Exception $exception): string {
    if ($exception instanceof StopRunnerException) {
      // This is a major error and must be seen at all times.
      return Verbosity::NORMAL;
    }

    return Verbosity::NORMAL;
  }

}
