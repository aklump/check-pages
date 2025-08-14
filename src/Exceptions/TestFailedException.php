<?php

namespace AKlump\CheckPages\Exceptions;

class TestFailedException extends StopRunnerException {

  /**
   * @var \AKlump\CheckPages\Output\Message\Message[]
   */
  private array $messages;

  /**
   * @param array $config
   *   The configuration of the test that failed.
   * @param null $message_or_exception
   *   A string message or an exception whose message will be used.
   * @param \AKlump\CheckPages\Output\Message\Message[] $messages
   */
  public function __construct(array $config, $message_or_exception = NULL, $messages = []) {
    $this->messages = $messages;
    // TODO Change arg 1 to be a Test instance.
    $message = sprintf("Test failed with the following test configuration:\n%s", json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    if ($message_or_exception instanceof \Exception) {
      $message = $message_or_exception->getMessage() . PHP_EOL . PHP_EOL . $message;
      parent::__construct($message, $message_or_exception->getCode(), $message_or_exception);
    }
    else {
      parent::__construct($message_or_exception . PHP_EOL . PHP_EOL . $message);
    }
  }

  public function getMessages(): array {
    return $this->messages;
  }

}
