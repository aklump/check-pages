<?php

namespace AKlump\CheckPages\Handlers;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Exceptions\TestFailedException;
use ParseCsv\Csv;
use AKlump\CheckPages\Event\AssertEventInterface;

/**
 * Implements the Tablular Data (table) handler.
 */
final class Table implements HandlerInterface {

  /**
   * {@inheritdoc}
   */
  public static function getId(): string {
    return 'table';
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::ASSERT_CREATED => [
        function (AssertEventInterface $event) {
          $assert = $event->getAssert();
          if (!$assert->has('table')) {
            return;
          }
          try {
            (new self())->handleAssert($event);
          }
          catch (\Exception $e) {
            throw new TestFailedException($event->getTest()
              ->getConfig(), $e);
          }
        },
      ],
    ];
  }

  private function handleAssert(AssertEventInterface $event) {
    try {
      $assert = $event->getAssert();
      $pointer = $assert->get('table');
      list($row_key, $column) = $this->parsePointer($pointer);
      list($header, $rows) = $this->parseTabularData($assert->getHaystack()[0]);

      if (is_numeric($column)) {
        $column_key = $column;
        $column = $header[$column_key];
      }
      else {
        $column_key = array_search($column, $header);
      }

      if ('header' === $row_key) {
        $haystack = $header;
        if ($column) {
          if (FALSE === $column_key) {
            $haystack = [];
          }
          else {
            $haystack = [$haystack[$column_key]];
          }
        }
      }
      else {
        $haystack = $rows[$row_key] ?? [];
        if ($haystack && $column) {
          if (FALSE === $column_key) {
            $haystack = [];
          }
          else {
            $haystack = [$haystack[$column]];
          }
        }
      }
      $assert->setHaystack($haystack);
      $assert->setSearch($this->getId(), $pointer);
    }
    catch (\Exception $e) {
      throw new TestFailedException($event->getTest()
        ->getConfig(), $e->getMessage());
    }
  }

  /**
   * Parses a given pointer and returns the row (and column) indicator(s).
   *
   * @param string $pointer The pointer to parse, e.g. "/header/do" or "/0/do"
   * or "/0/2" or "/header/1".
   *
   * @return array An array containing the row and optionally, the column.
   * @throws \InvalidArgumentException If the pointer is invalid.
   */
  private function parsePointer(string $pointer): array {
    $parts = explode('/', trim($pointer, '/'));
    if (!preg_match('#header|\d+#', $parts[0] ?? '', $matches)) {
      throw new \InvalidArgumentException('Invalid pointer');
    }

    return [$parts[0], $parts[1] ?? NULL];
  }

  /**
   * Parse tabular data and return an array containing titles and data.
   *
   * @param string $tabular_data The tabular data string to be parsed.
   *
   * @return array An array containing titles and data.
   */
  private function parseTabularData(string $tabular_data): array {
    $parser = new Csv();
    $enclosure = $this->autoDetectEnclosure($tabular_data);
    $parser->delimiter = $parser->autoDetectionForDataString($tabular_data, TRUE, NULL, NULL, $enclosure);
    $parser->parse($tabular_data);

    return [$parser->titles, $parser->data];
  }

  private function autoDetectEnclosure(string $tabular_data): string {
    if (substr(ltrim($tabular_data), 0, 1) === '"') {
      return '"';
    }
    if (str_word_count($tabular_data, 0, '","') > 1) {
      return '"';
    }

    return '';
  }

}
