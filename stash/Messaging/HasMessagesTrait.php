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
}
