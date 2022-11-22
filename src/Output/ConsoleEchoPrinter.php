<?php

namespace AKlump\CheckPages\Output;

use AKlump\LoftLib\Bash\Color;
use AKlump\Messaging\MessageInterface;
use Symfony\Component\Console\Output\OutputInterface;
use AKlump\Messaging\MessengerInterface;
use AKlump\Messaging\MessageType;

/**
 * Echos test feedback to the CLI console.
 */
final class ConsoleEchoPrinter implements MessengerInterface {

  const INVERT = 1;

  const INVERT_FIRST = 2;

  /**
   * @var \AKlump\CheckPages\Output\VerboseDirective
   */
  private $verboseDirective;

  /**
   * @var \Symfony\Component\Console\Output\OutputInterface
   */
  private $output;

  public function __construct(OutputInterface $output) {
    $this->output = $output;
    $this->setVerboseDirective(new VerboseDirective(''));
  }

  public function deliver(MessageInterface $message, int $flags = NULL) {
    if (!$this->verboseDirective->intersectsWith($message->getVerboseDirective())) {
      return;
    }
    $colors = [
      MessageType::ERROR => 'red',
      MessageType::SUCCESS => 'green',
      MessageType::INFO => 'blue',
      MessageType::DEBUG => 'light gray',
    ];
    $inverted = [
      MessageType::ERROR => 'white on red',
      MessageType::SUCCESS => 'white on green',
      MessageType::INFO => 'white on blue',
      MessageType::DEBUG => 'black on light gray',
    ];

    $color = $colors[$message->getMessageType()];
    if ($flags & self::INVERT) {
      $color = $inverted[$message->getMessageType()];
    }

    $flat_message = strval($message);
    if ($flags & self::INVERT_FIRST) {
      $lines = explode(PHP_EOL, $flat_message);
      $color = $inverted[$message->getMessageType()];
      $this->output->writeln(Color::wrap($color, array_shift($lines)));
      $flat_message = implode(PHP_EOL, $lines);
      $color = $colors[$message->getMessageType()];
    }

    $message = Color::wrap($color, $flat_message);
    $this->output->writeln($message);
  }

  public function getVerboseDirective(): VerboseDirective {
    return $this->verboseDirective;
  }

  /**
   * {@inheritdoc}
   */
  public function setVerboseDirective(VerboseDirective $verbose_directive): MessengerInterface {
    $this->verboseDirective = $verbose_directive;

    return $this;
  }

}
