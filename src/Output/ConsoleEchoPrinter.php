<?php

namespace AKlump\CheckPages\Output;

use AKlump\LoftLib\Bash\Color;
use AKlump\Messaging\MessageInterface;
use AKlump\Messaging\MessageType;
use AKlump\Messaging\MessengerInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Echos test feedback to the CLI console.
 */
final class ConsoleEchoPrinter implements MessengerInterface {

  private $callbacks = [];

  /**
   * @var \AKlump\CheckPages\Output\VerboseDirective
   */
  private $directive;

  /**
   * @var \Symfony\Component\Console\Output\OutputInterface
   */
  private $output;

  public function __construct(OutputInterface $output, int $verbosity = NULL) {
    $this->directive = VerboseDirective::createFromInt($verbosity ?? Verbosity::NORMAL);
    $this->output = $output;
  }

  public function addProcessor(callable $callback): MessengerInterface {
    $this->callbacks[] = $callback;

    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * @see \AKlump\CheckPages\Output\Flags
   */
  public function deliver(MessageInterface $message, int $flags = NULL) {
    $always_show_message = strval($message->getVerboseDirective()) === '';
    if (!$always_show_message
      && !$this->directive->intersectsWith($message->getVerboseDirective())) {
      return;
    }
    $colors = [
      MessageType::ERROR => 'red',
      MessageType::SUCCESS => 'green',
      MessageType::INFO => 'blue',
      MessageType::DEBUG => 'light gray',
      MessageType::TODO => 'orange',
    ];
    $inverted = [
      MessageType::ERROR => 'white on red',
      MessageType::SUCCESS => 'white on green',
      MessageType::INFO => 'white on blue',
      MessageType::DEBUG => 'black on light gray',
      MessageType::TODO => 'white on orange',
    ];

    $color = $colors[$message->getMessageType()];
    if ($flags & Flags::INVERT) {
      $color = $inverted[$message->getMessageType()];
    }

    $lines = $message->getMessage();
    foreach ($this->callbacks as $callback) {
      $lines = $callback($lines, $message);
    }

    if ($flags & Flags::INVERT_FIRST_LINE) {
      $color = $inverted[$message->getMessageType()];
      $first_line = array_shift($lines);

      // This gives a little right padding to our bg color.
      if (substr($first_line, -1) !== ' ') {
        $first_line .= ' ';
      }
      $this->output->writeln(Color::wrap($color, $first_line));
      $color = $colors[$message->getMessageType()];
    }

    if ($lines) {
      $flat_message = implode(PHP_EOL, $lines);
      $message = Color::wrap($color, $flat_message);
      $this->output->writeln($message);
    }
  }

}
