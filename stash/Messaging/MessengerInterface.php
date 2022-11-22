<?php

namespace AKlump\Messaging;

interface MessengerInterface {

  public function deliver(MessageInterface $message, int $flags = NULL);
}
