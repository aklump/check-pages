<?php

namespace AKlump\CheckPages\Output\Messenger;

use AKlump\CheckPages\Output\Flags;
use AKlump\CheckPages\Output\VerboseDirective;
use AKlump\CheckPages\Output\Verbosity;
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

    $message_type = $message->getMessageType();
    $lines = $message->getMessage();
    foreach ($this->callbacks as $callback) {
      $lines = $callback($lines, $message);
    }

    $lines = $this->alterMessageBodyByMessageType($lines, $message_type);

    if ($flags & Flags::INVERT_FIRST_LINE) {
      $first_line = array_shift($lines);

      // This gives a little right padding to our bg color.
      if (substr($first_line, -1) !== ' ') {
        $first_line .= ' ';
      }
      $first_line_color = $this->getColor($message_type, TRUE);
      $this->output->writeln(Color::wrap($first_line_color, $first_line));
    }

    if ($lines) {
      $flat_message = implode(PHP_EOL, $lines);
      $color = $this->getColor($message_type);
      $message = Color::wrap($color, $flat_message);
      $this->output->writeln($message);
    }
  }

  private function getColorOptions(bool $invert = FALSE): array {
    if ($invert) {
      return [
        MessageType::EMERGENCY => 'red on white',
        MessageType::ERROR => 'white on red',
        MessageType::SUCCESS => 'white on green',
        MessageType::INFO => 'white on blue',
        MessageType::DEBUG => 'black on light gray',
        MessageType::TODO => 'white on orange',
        'skipped' => 'white on yellow',
      ];
    }

    return [
      MessageType::EMERGENCY => 'white on red',
      MessageType::ERROR => 'red',
      MessageType::SUCCESS => 'green',
      MessageType::INFO => 'blue',
      MessageType::DEBUG => 'light gray',
      MessageType::TODO => 'orange',
      'skipped' => 'yellow',
    ];
  }

  private function getColor(string $message_type, bool $invert = FALSE): string {
    $colors = $this->getColorOptions($invert);

    return $colors[$message_type] ?? $colors[MessageType::INFO];
  }

  private function alterMessageBodyByMessageType(array $lines, string $message_type) {
    if (MessageType::EMERGENCY === $message_type) {
      return $this->themeEmergency($lines);
    }

    return $lines;
  }

  private function themeEmergency(array $lines) {
    $padding = '   ';
    $lines = array_map(function ($line) use ($padding) {
      return "$padding$line$padding";
    }, $lines);
    $longest_line = max(array_map('strlen', $lines));
    $lines = array_map(function ($line) use ($longest_line) {
      return str_pad($line, $longest_line);
    }, $lines);
    $border = str_repeat(' ', $longest_line);

    return array_merge([$border], $lines, [$border]);
  }

}
