<?php

namespace AKlump\CheckPages\Messaging;

/**
 * A class that represents a normal (not verbose) message.
 */
abstract class MessageBase implements MessageInterface {

  protected string $icon = '';

  /** @var string */
  private $messageType;

  /** @var array */
  private $message;

  public function __construct(array $message, string $message_type) {
    $this->messageType = $message_type;
    $this->message = array_map('strval', array_values($message));
  }

  /**
   * @return string
   *
   * @see \AKlump\CheckPages\Messaging\MessageType
   */
  public function getMessageType(): string {
    return $this->messageType;
  }

  public function setMessage(array $message): self {
    $this->message = array_values($message);

    return $this;
  }

  public function setIcon(string $icon): self {
    $this->icon = $icon;

    return $this;
  }

  public function getIcon(): string {
    return $this->icon;
  }

  public function setMessageType(string $type): self {
    $this->messageType = $type;
    return $this;
  }

  /**
   * @return array
   */
  public function getMessage(): array {
    return $this->message;
  }

  public function __toString(): string {
    $message = $this->message;
    if (!empty($this->icon)) {
      $message[0] = $this->icon . ' ' . ($message[0] ?? '');
    }

    return implode(PHP_EOL, $message);
  }

}
