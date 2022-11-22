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

  /**
   * Get the message type.
   *
   * @return string
   *
   * @see \AKlump\Messaging\MessageType
   */
  public function getMessageType(): string;

  /**
   * @return string
   *   The stringified version of the message.
   */
  public function __toString(): string;

}
