<?php

namespace AKlump\CheckPages\Output;

use AKlump\Messaging\Processor;

/**
 * Deliver all messages to a messenger with pre-defined processors.
 */
class MessageDelivery {

  public function __invoke(\AKlump\Messaging\MessengerInterface $messenger, array $messages): void {
    if (!empty($messages)) {
      $messenger
        ->addProcessor([Processor::class, 'wordWrap'])
        ->addProcessor([Processor::class, 'tree']);
      foreach ($messages as $message) {
        $messenger->deliver($message);
      }
    }
  }
}
