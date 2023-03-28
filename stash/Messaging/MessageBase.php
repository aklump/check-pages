<?php

namespace AKlump\Messaging;

/**
 * A class that represents a normal (not verbose) message.
 */
abstract class MessageBase implements MessageInterface {

  /** @var string */
  private $messageType;

  /** @var array */
  private $message;

  public function __construct(array $message, string $message_type) {
    $info = new \ReflectionClass(MessageType::class);
    $valid_values = $info->getConstants();
    if (!in_array($message_type, $valid_values)) {
      throw new \InvalidArgumentException(sprintf('Invalid message type: %s', $message_type));
    }
    $this->messageType = $message_type;
    $this->message = array_map('strval', array_values($message));
  }

  /**
   * @return string
   *
   * @see \AKlump\Messaging\MessageType
   */
  public function getMessageType(): string {
    return $this->messageType;
  }

  public function setMessage(array $message): void {
    $this->message = $message;
  }

  public function setMessageType(string $type): void {
    $this->messageType = $type;
  }

  /**
   * @return array
   */
  public function getMessage(): array {
    return $this->message;
  }

  public function __toString(): string {
    return implode(PHP_EOL, $this->message);
  }

}
