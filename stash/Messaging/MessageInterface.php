<?php

namespace AKlump\Messaging;

interface MessageInterface {

  /**
   * Get the message broken into lines.
   *
   * @return array
   *   Each element is presumed to be a single line.
   */
  public function getMessage(): array;

  public function setMessage(array $message): void;

  /**
   * Get the message type.
   *
   * @return string
   *
   * @see \AKlump\Messaging\MessageType
   */
  public function getMessageType(): string;

  public function setMessageType(string $type): void;

  /**
   * @return string
   *   The stringified version of the message.
   */
  public function __toString(): string;

}
