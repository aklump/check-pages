<?php

namespace AKlump\CheckPages\Messaging;

interface HasMessagesInterface {

  public function addMessage(MessageInterface $message): void;

  public function getMessages(): array;

  public function setMessages(array $messages): void;

}
