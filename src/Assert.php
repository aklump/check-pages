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
  const SEARCH_ALL = 1;

  /**
   * @var int
   */
  const SEARCH_DOM = 2;

  /**
   * @var int
   */
  const SEARCH_XPATH = 3;

  /**
   * @var int
   */
  const ASSERT_COUNT = 1;

  /**
   * @var int
   */
  const ASSERT_EXACT = 2;

  /**
   * @var int
   */
  const ASSERT_TEXT = 3;

  /**
   * @var int
   */
  const ASSERT_MATCH = 4;

  /**
   * @var int
   */
  const ASSERT_SUBSTRING = 5;

  /**
   * @var int
   */
  private $searchType;

  /**
   * @var mixed
   */
  private $searchValue;

  /**
   * @var int
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
  public function setSearch(int $type, $value = NULL): self {
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
  public function setAssert(int $type, $expected): self {
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
        $haystack = $this->haystack;
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
          $haystack = trim($haystack->text());
          break;

        case self::ASSERT_MATCH:
        case self::ASSERT_EXACT:
          $haystack = trim($haystack->html());
          break;
      }

    }

    switch ($this->assertType) {
      case self::ASSERT_SUBSTRING:
        $pass = strpos($haystack, $this->assertValue) !== FALSE;
        if (!$pass) {
          $this->reason = sprintf("Unable to find:\n\n>>> %s\n\n", $this->assertValue);
        }

        return $pass;

      case self::ASSERT_COUNT:
        return $this->assertCount($this->assertValue, count($haystack));

      case self::ASSERT_TEXT:
      case self::ASSERT_EXACT:
        $pass = $haystack == $this->assertValue;
        if (!$pass) {
          $this->reason = sprintf("The actual value\n\n>>> %s\n\n is not the expected\n\n>>> %s", $haystack, $this->assertValue);
        }

        return $pass;

      case self::ASSERT_MATCH:
        $pass = preg_match($this->assertValue, $haystack);
        if (!$pass) {
          $this->reason = sprintf("Unable to match using \"%s\".", $this->assertValue, $haystack);
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

}
