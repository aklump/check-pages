<?php

namespace AKlump\Messaging;

final class Processor {

  public static function wordWrap(array $lines) {
    $wrapped = [];
    foreach ($lines as $line) {
      $wrapped = array_merge($wrapped, explode(PHP_EOL, wordwrap($line, 110)));
    }

    return $wrapped;
  }

  public static function tree(array $lines) {
    $prefix = '├── ';
    foreach ($lines as &$line) {
      $line = $prefix . $line;
      $prefix = '│   ';
    }

    return $lines;
  }

}
