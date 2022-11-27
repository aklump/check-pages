<?php

namespace AKlump\Messaging;

trait HasMessagesTrait {

  private $hasMessagesTraitMessages = [];

  public function addMessage(MessageInterface $message) {
    $this->hasMessagesTraitMessages[] = $message;
  }

  public function getMessages(): array {
    return $this->hasMessagesTraitMessages;
  }

  public function setMessages(array $messages): self {
    $this->hasMessagesTraitMessages = [];
    foreach ($messages as $message) {
      $this->addMessage($message);
    }

    return $this;
  }
}
