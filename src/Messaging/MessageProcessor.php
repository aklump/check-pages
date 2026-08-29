<?php

namespace AKlump\CheckPages\Messaging;

use AKlump\CheckPages\Helpers\GetRealStringLength;
use Symfony\Component\Console\Terminal;

final class MessageProcessor {

  const MARGIN_NORMAL = 1;

  const MARGIN_WIDE = 3;

  const BRANCH = '├── ';

  const TRUNK = '│   ';

  public static function wordWrap(MessageInterface $message, int $width) {
    $lines = $message->getMessage();
    $wrapped = [];
    foreach ($lines as $line) {
      $wrapped = array_merge($wrapped, explode(PHP_EOL, wordwrap($line, $width)));
    }

    $message->setMessage($lines);
  }

  public static function tree(array $lines) {
    $prefix = self::BRANCH;
    foreach ($lines as &$line) {
      if (empty(trim($line))) {
        $prefix = self::TRUNK;
      }
      $line = $prefix . $line;
      $prefix = self::TRUNK;
    }

    return $lines;
  }

  public static function indent(MessageInterface $message, string $count): void {
    $lines = $message->getMessage();
    $lines = array_map(fn($line) => str_repeat(' ', $count) . $line, $lines);
    $message->setMessage($lines);
  }

  public static function topMargin(MessageInterface $message, int $margin_type = self::MARGIN_NORMAL): void {
    $lines = $message->getMessage();
    $lines = array_values($lines);
    if ('' !== trim($lines[0] ?? '')) {
      for ($i = 0; $i < $margin_type; ++$i) {
        array_unshift($lines, '');
      }
    }
    $message->setMessage($lines);
  }

  public static function fullWidth(MessageInterface $message) {
    $lines = $message->getMessage();
    $width = (new Terminal())->getWidth() ?? 80;
    foreach ($lines as &$line) {
      $length = (new GetRealStringLength())($line);
      $line .= str_repeat(' ', $width - min($width, $length));
    }
    unset($line);

    $message->setMessage($lines);
  }

  public static function bottomMargin(MessageInterface $message, int $margin_type = self::MARGIN_NORMAL): void {
    $lines = $message->getMessage();
    $countLines = function () use (&$lines, &$count, &$last_key) {
      $count = count($lines);
      $last_key = $count - 1;
    };
    $countLines();
    $expectedCount = $count + $margin_type;
    while ($count < $expectedCount || '' !== trim($lines[$last_key] ?? '')) {
      $lines[] = '';
      $countLines();
    }

    $message->setMessage($lines);
  }

}
