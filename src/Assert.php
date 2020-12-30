<?php

namespace AKlump\CheckPages;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Handle the search and asserts.
 */
final class Assert {

  /**
   * @var int
   */
  const SEARCH_ALL = 'page';

  /**
   * @var int
   */
  const SEARCH_DOM = 'dom';

  /**
   * @var int
   */
  const SEARCH_XPATH = 'xpath';

  /**
   * @var int
   */
  const ASSERT_COUNT = 'count';

  /**
   * @var int
   */
  const ASSERT_EXACT = 'exact';

  /**
   * @var int
   */
  const ASSERT_TEXT = 'text';

  /**
   * @var int
   */
  const ASSERT_MATCH = 'match';

  /**
   * @var int
   */
  const ASSERT_SUBSTRING = 'contains';

  /**
   * @var string
   */
  private $searchType;

  /**
   * @var mixed
   */
  private $searchValue;

  /**
   * @var string
   */
  private $assertType;

  /**
   * @var mixed
   */
  private $assertValue;

  /**
   * @var string
   */
  private $reason = '';

  /**
   * Assert constructor.
   *
   * @param string $haystack
   *   The starting string to search and assert within.
   */
  public function __construct(string $haystack) {
    $this->haystack = $haystack;
  }

  /**
   * Set the type of search to perform.
   *
   * @param int $type
   *   The search type.
   * @param null $value
   *   The search value, if needed.
   *
   * @return $this
   *   Self for chaining.
   */
  public function setSearch(string $type, $value = NULL): self {
    $this->searchType = $type;
    $this->searchValue = $value;

    return $this;
  }

  /**
   * Set the type of assertion to execute.
   *
   * @param int $type
   *   The assert type.
   * @param mixed $expected
   *   The expected value.
   *
   * @return $this
   *   Self for chaining.
   */
  public function setAssert(string $type, $expected): self {
    $this->assertType = $type;
    $this->assertValue = $expected;

    return $this;
  }

  /**
   * Run the search and assertion.
   *
   * @return bool
   *   True or false.
   *
   * @see \AKlump\CheckPages\Assert::getReason()
   */
  public function run(): bool {
    switch ($this->searchType) {
      case self::SEARCH_ALL:
        $haystack = [$this->haystack];
        break;

      case self::SEARCH_DOM:
        $crawler = new Crawler($this->haystack);
        $haystack = $crawler->filter($this->searchValue);
        break;

      case self::SEARCH_XPATH:
        $crawler = new Crawler($this->haystack);
        $haystack = $crawler->filterXPath($this->searchValue);
        break;
    }

    // The asserts run against an array, so if $haystack is a Crawler, it must
    // be converted to an array before the asserts are tested.
    if ($haystack instanceof Crawler) {
      if (!$haystack->getNode(0)) {

        // If we are expecting a count of 0, then this is a pass.
        if ($this->assertType === self::ASSERT_COUNT && $this->assertValue === 0) {
          return TRUE;
        }
        $this->reason = sprintf('"%s" does not exist in the DOM.', $this->searchValue);

        return FALSE;
      }

      switch ($this->assertType) {
        case self::ASSERT_TEXT:
          $haystack = $haystack->each(function ($node) {
            return trim($node->text());
          });
          break;

        case self::ASSERT_MATCH:
        case self::ASSERT_EXACT:
          $haystack = $haystack->each(function ($node) {
            return trim($node->html());
          });
          break;
      }
    }

    switch ($this->assertType) {
      case self::ASSERT_SUBSTRING:
        foreach ($haystack as $item) {
          $pass = $this->applyCallbackWithVariations($item, function ($item_variation) {
            return strpos($item_variation, $this->assertValue) !== FALSE;
          });
          if ($pass) {
            break;
          }
        }
        if (!$pass) {
          $this->reason = sprintf("Unable to find:\n\n>>> %s\n\n", $this->assertValue);
        }

        return $pass;

      case self::ASSERT_COUNT:
        return $this->assertCount($this->assertValue, count($haystack));

      case self::ASSERT_TEXT:
        foreach ($haystack as $item) {
          $pass = $this->applyCallbackWithVariations($item, function ($item_variation) {
            return $item_variation == $this->assertValue;
          });
          if ($pass) {
            break;
          }
        }
        if (!$pass) {
          $haystack = '"' . implode('",, "', $haystack) . '"';
          $this->reason = sprintf("The actual value\n\n>>> %s\n\n is not the expected\n\n>>> %s", $haystack, $this->assertValue);
        }

        return $pass;

      case self::ASSERT_EXACT:
        foreach ($haystack as $item) {
          $pass = $item == $this->assertValue;
          if ($pass) {
            break;
          }
        }
        if (!$pass) {
          $haystack = '"' . implode('",, "', $haystack) . '"';
          $this->reason = sprintf("The actual value\n\n>>> %s\n\n is not the expected\n\n>>> %s", $haystack, $this->assertValue);
        }

        return $pass;

      case self::ASSERT_MATCH:
        foreach ($haystack as $item) {
          $pass = preg_match($this->assertValue, $item);
          if ($pass) {
            break;
          }
        }
        if (!$pass) {
          $this->reason = sprintf("Unable to match using \"%s\".", $this->assertValue);
        }

        return $pass;
    }

    $this->reason = 'Invalid assertion';

    return FALSE;
  }

  /**
   * Get the failure reason.
   *
   * @return string
   *   The reason for failure.
   */
  public function getReason(): string {
    return $this->reason;
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    $prefix = '';
    switch ($this->assertType) {
      case static::ASSERT_MATCH:
        $prefix = sprintf('Match against RegExp "%s"', $this->assertValue);
        break;

      case static::ASSERT_TEXT:
        $prefix = sprintf('Convert to plaintext and compare to "%s"', $this->assertValue);
        break;

      case static::ASSERT_EXACT:
        $prefix = sprintf('Compare exact value to "%s"', $this->assertValue);
        break;

      case static::ASSERT_SUBSTRING:
        $prefix = sprintf('Find "%s"', $this->assertValue);
        break;

      case static::ASSERT_COUNT:
        $prefix = 'Count elements';
        break;
    }

    switch ($this->searchType) {
      case static::SEARCH_ALL:
        $suffix = 'on the page';
        break;

      case static::SEARCH_XPATH:
        $suffix = sprintf('after filtering by XPath "%s"', $this->searchValue);
        break;

      case static::SEARCH_DOM:
        $suffix = sprintf('after selecting with "%s"', $this->searchValue);
        break;
    }

    return implode(' ', [$prefix, $suffix]) . '.';
  }

  /**
   * Helper function to process a count assertion.
   *
   * @param int|string $expected
   *   This may be an integer or a string like ">= 1", et al.
   * @param int $actual
   *   The actual count.
   *
   * @return bool
   *   True if the actual matches expected.
   */
  private function assertCount($expected, int $actual): bool {
    if (is_numeric($expected)) {
      $pass = $actual === $expected;
      if (!$pass) {
        $this->reason = sprintf('Actual count %d is not equal to the expected count of %d.', $actual, $expected);
      }
    }
    else {
      preg_match('/([><=]+)\s*(\d+)/', $expected, $matches);
      list(, $comparator, $expected) = $matches;
      $expected = $matches[2];
      switch ($comparator) {
        case '>':
          $pass = $actual > $expected;
          if (!$pass) {
            $this->reason = sprintf('Actual count %d was not > expected %d', $actual, $expected);
          }
          break;

        case '>=':
          $pass = $actual >= $expected;
          if (!$pass) {
            $this->reason = sprintf('Actual count %d was not >= expected %d', $actual, $expected);
          }
          break;

        case '<':
          $pass = $actual < $expected;
          if (!$pass) {
            $this->reason = sprintf('Actual count %d was not < expected %d', $actual, $expected);
          }
          break;

        case '<=':
          $pass = $actual <= $expected;
          if (!$pass) {
            $this->reason = sprintf('Actual count %d was not <= expected %d', $actual, $expected);
          }
          break;
      }
    }

    return $pass;
  }

  /**
   * Return information about available selectors.
   *
   * @return \AKlump\CheckPages\Help[]
   */
  public function getSelectorsInfo(): array {
    return [
      new Help(self::SEARCH_DOM, "Select from the DOM using CSS selectors.", [
        'p.summary',
        'main',
        '.story__title',
      ]),
      new Help(self::SEARCH_XPATH, "Select from the DOM using XPath selectors.", ['(//*[contains(@class, "block-title")])[3]']),
    ];
  }

  /**
   * Return information about available assertions.
   *
   * @return \AKlump\CheckPages\Help[]
   */
  public function getAssertionsInfo(): array {
    return [
      new Help(self::ASSERT_SUBSTRING, 'Pass if the value is found in the selection.', ['foo']),
      new Help(self::ASSERT_COUNT, 'Pass if equal to the number of items in the selection.', [2]),
      new Help(self::ASSERT_EXACT, "Pass if the selection's markup matches exactly.", ['<em>lorem <strong>ipsum dolar</strong> sit amet.</em>']),
      new Help(self::ASSERT_MATCH, 'Applies a REGEX expression against the selection.', ['/copyright\s+20\d{2}$/']),
      new Help(self::ASSERT_TEXT, "Pass if the selection's text value (all markup removed) matches exactly.", ['lorem ipsum dolar sit amet.']),
    ];
  }

  /**
   * Apply a callback using variations of $item.
   *
   * @param $item
   *   This may contain special characters like &nbps;, which we want to match
   *   against a ' ' for loose-matching.  This value and variances based on
   *   string replacements will be passed to $callback.
   * @param callable $callback
   *   This should receive one argument and return a true based on comparing
   *   that item to $this->asserValue.  The callback will be called more than
   *   once, using variations of $item.  Only one pass is necessary for a true
   *   response.
   *
   * @return bool
   *   True if the callback returns true at least once.
   */
  private function applyCallbackWithVariations($item, callable $callback): bool {
    return $callback($item)

      // Replace ASCII 160 with 32.
      || $callback(str_replace(' ', ' ', $item));
  }

}
