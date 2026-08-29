<?php

namespace AKlump\CheckPages\Output;

use AKlump\CheckPages\Messaging\MessengerInterface;
use AKlump\CheckPages\Messaging\MessageProcessor;
use Symfony\Component\Console\Terminal;

/**
 * Deliver all messages to a messenger with pre-defined processors.
 */
class MessageDelivery {

  /**
   * @param \AKlump\CheckPages\Messaging\MessengerInterface $messenger
   * @param \AKlump\CheckPages\Messaging\MessageInterface[] $messages
   *
   * @return void
   */
  public function __invoke(MessengerInterface $messenger, array $messages): void {
    if (empty($messages)) {
      return;
    }

    $rightMargin = 10;
    $lineWidth = (new Terminal())->getWidth() - $rightMargin;
    //    $lineWidth = (new Terminal())->getWidth() - mb_strlen(MessageProcessor::TRUNK);

    foreach ($messages as $message) {
      MessageProcessor::wordWrap($message, $lineWidth);
      //      $lines = MessageProcessor::tree($lines);

      $messenger->writeln($message);
    }
  }
}
