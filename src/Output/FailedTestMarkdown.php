<?php

namespace AKlump\CheckPages\Output;

use AKlump\CheckPages\CheckPages;
use AKlump\CheckPages\Parts\Test;

/**
 * Writes a markdown file for failing tests.
 */
final class FailedTestMarkdown implements ResultsToFileInterface {

  /**
   * The title, token is {{ suite.id }}.
   *
   * @var string
   */
  private $title = "# Suite: {{ suite.id }}";

  /**
   * The subtitle, token is {{ suite.why }}.
   *
   * @var string
   */
  private $subtitle = "## {{ suite.why }}";

  /**
   * The problems list label, token is {{ suite.id }}.
   *
   * @var string
   */
  private $listLabel = "### Problems";

  /**
   * FailedTestMarkdown constructor.
   *
   * @param string $output_filename
   *   The filename to write to.
   * @param \AKlump\CheckPages\Parts\Test $test
   *   The test instance.
   */
  public function __construct(string $output_filename, Test $test) {
    $this->basename = pathinfo($output_filename, PATHINFO_FILENAME) . '.md';
    $this->test = $test;
  }

  /**
   * Create a new instance.
   *
   * @param string $output_filename
   *   The filename to write to.
   * @param \AKlump\CheckPages\Parts\Test $test
   *   The test instance.
   */
  public static function output(string $output_filename, Test $test = NULL) {
    $obj = new static($output_filename, $test);

    return $obj->fail($test->getResults());
  }

  /**
   * Process failures in a test run.
   *
   * @param array $debug
   *   The debug array from the runner.
   */
  private function fail(array $debug) {
    $markdown_failure = array_filter(array_map(function ($item) {
      $is_todo = in_array('todo', ($item['tags'] ?? []));
      if (!$is_todo || !in_array($item['level'], ['error']) || '└── Test failed.' === $item['data']) {
        return NULL;
      }
      $tokens = [['├──', '└──'], ['1. ', '1. ']];
      if ($is_todo) {
        $tokens = [['├──', '└──'], ['- [ ] ', '- [ ] ']];
      }

      return str_replace($tokens[0], $tokens[1], $item['data']);
    }, $debug));

    if (!$markdown_failure) {
      return;
    }

    $runner = $this->test->getRunner();
    $runner->writeToFile($this->basename, [
      str_replace('{{ suite.id }}', $this->test->getSuite()
        ->id(), $this->title),
      '',
    ], 'w+');
    $subtitle = $this->test->getConfig()['why'] ?? '';
    if ($subtitle) {
      $runner->writeToFile($this->basename, [
        str_replace('{{ suite.why }}', $subtitle, $this->subtitle),
        '',
      ]);
    }
    $url = $this->test->getRelativeUrl();
    $url = $runner->url($url);
    $runner->writeToFile($this->basename, [
      "<$url>",
      '',
    ], 'w+');
    $runner->writeToFile($this->basename, array_merge([
      $this->listLabel,
      '',
    ], $markdown_failure));
  }

}
