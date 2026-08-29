<?php

namespace AKlump\CheckPages\Output\Messenger;

use AKlump\CheckPages\Messaging\MessageInterface;
use AKlump\CheckPages\Messaging\MessengerInterface;
use AKlump\CheckPages\Messaging\SectionAwareMessengerInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;

/**
 * Use this to as a master printer that will run multiple.
 *
 * You might use this for a console echo and a logger at the same time.
 */
class MultiPrinter implements MessengerInterface, SectionAwareMessengerInterface {

  /**
   * @var \AKlump\CheckPages\Messaging\MessengerInterface[]
   */
  private array $printers;

  /**
   * @param \AKlump\CheckPages\Messaging\MessengerInterface[] $printers
   */
  public function __construct(array $printers) {
    $this->printers = $printers;
  }

  /**
   * @inheritDoc
   */
  public function writeln(MessageInterface $message): void {
    foreach ($this->printers as $printer) {
      $printer->writeln($message);
    }
  }

  public function getVerbosity(): int {
    $flags = 0;
    foreach ($this->printers as $printer) {
      $flags |= $printer->getVerbosity();
    }

    return $flags;
  }

  public function section(string $messageType): ConsoleSectionOutput {
    foreach ($this->printers as $printer) {
      if ($printer instanceof SectionAwareMessengerInterface) {
        return $printer->section($messageType);
      }
    }

    throw new \RuntimeException('No section-aware printer is available.');
  }

  public function resetSections(): void {
    foreach ($this->printers as $printer) {
      if ($printer instanceof SectionAwareMessengerInterface) {
        $printer->resetSections();
      }
    }
  }

}
