<?php

namespace AKlump\Messaging;

trait HasMessagesTrait  {

  private $hasMessagesTraitMessages = [];

  public function addMessage(MessageInterface $message): void {
    $this->hasMessagesTraitMessages[] = $message;
  }

  public function getMessages(): array {
    return $this->hasMessagesTraitMessages;
  }

  public function setMessages(array $messages): void {
    $this->hasMessagesTraitMessages = [];
    foreach ($messages as $message) {
      $this->addMessage($message);
    }
  }
}
