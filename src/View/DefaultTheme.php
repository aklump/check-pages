<?php

namespace AKlump\CheckPages\View;

use AKlump\CheckPages\Output\Icons;
use AKlump\CheckPages\Messaging\MessageInterface;
use AKlump\CheckPages\Messaging\MessageProcessor;

class DefaultTheme {

  public function passedAssertion(MessageInterface $message): void {
    if (empty($message->getIcon())) {
      $message->setIcon(Icons::COMPLETE);
    }
  }

  public function failedAssertion(MessageInterface $message): void {
    if (empty($message->getIcon())) {
      $message->setIcon(Icons::INCOMPLETE);
    }
  }

  public function divider(MessageInterface $message): void {
    MessageProcessor::fullWidth($message);
  }

  public function code(MessageInterface $message): void {
    MessageProcessor::fullWidth($message);
  }

  public function testSource(MessageInterface $message): void {
    MessageProcessor::fullWidth($message);
    MessageProcessor::bottomMargin($message);
  }

  public function emergency(MessageInterface $message): void {
    $lines = $message->getMessage();
    $padding = '   ';
    $lines = array_map(function ($line) use ($padding) {
      return "$padding$line$padding";
    }, $lines);
    $longest_line = max(array_map('strlen', $lines));
    $lines = array_map(function ($line) use ($longest_line) {
      return str_pad($line, $longest_line);
    }, $lines);
    $border = str_repeat(' ', $longest_line);

    $lines = array_merge([$border], $lines, [$border]);
    $message->setMessage($lines);
  }

  public function info(MessageInterface $message): void {
  }

  public function debug(MessageInterface $message): void {
  }

  public function error(MessageInterface $message): void {
  }

  public function skipped(MessageInterface $message): void {
  }

  public function success(MessageInterface $message): void {
  }

  public function todo(MessageInterface $message): void {
  }

  public function banner(MessageInterface $message): void {
    MessageProcessor::topMargin($message);
    MessageProcessor::fullWidth($message);
    MessageProcessor::bottomMargin($message);
  }

}
