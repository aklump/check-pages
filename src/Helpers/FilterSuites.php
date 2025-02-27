<?php

namespace AKlump\CheckPages\Helpers;

use AKlump\CheckPages\SuiteCollection;
use AKlump\CheckPages\Parts\Suite;

class FilterSuites {

  /**
   * @param \AKlump\CheckPages\SuiteCollection $suites
   * @param string $filter
   *   Only suites whose "group/id" matches the given regular expression pattern. If the pattern is not enclosed in delimiters, PHPUnit will enclose the pattern in / delimiters.
   *
   * @return \AKlump\CheckPages\SuiteCollection
   */
  public function __invoke(SuiteCollection $suites, string $filter): SuiteCollection {
    if (!$this->isRegularExpression($filter)) {

      //Look for exact match.
      /** @var SuiteCollection $exact_matches */
      $exact_matches = $suites->filter(function (Suite $suite) use ($filter) {
        return $filter === (string) $suite || $filter === $suite->id();
      });
      if (count($exact_matches) === 1) {
        return $exact_matches;
      }

      $filter = \sprintf('/%s/i', \str_replace(
        '/',
        '\\/',
        $filter
      ));
    }

    /** @var SuiteCollection $result */
    $result = $suites->filter(function (Suite $suite) use ($filter) {
      return preg_match($filter, (string) $suite);
    });

    return $result;
  }

  /**
   * @param string $filter
   *
   * @return bool
   *
   * @url
   */
  private function isRegularExpression(string $filter): bool {

    // A delimiter can be any non-alphanumeric, non-backslash, non-whitespace
    // character. Leading whitespace before a valid delimiter is silently
    // ignored.
    $valid_delimiter_regex = '/[^\w\\\s]/';
    $filter = trim($filter);
    $leading = substr($filter, 0, 1);

    return $leading === substr($filter, -1) && preg_match($valid_delimiter_regex, $leading);
  }
}
