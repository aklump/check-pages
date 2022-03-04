<?php

namespace AKlump\CheckPages;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Handle the search and asserts.
 */
final class Assert {

  /**
   * @var string
   */
  const SEARCH_ALL = 'page';

  /**
   * @var string
   */
  const MODIFIER_ATTRIBUTE = 'attribute';

  /**
   * @var string
   */
  const MODIFIER_PROPERTY = 'style';

  /**
   * @var string
   */
  const ASSERT_SETTER = 'set';

  /**
   * @var string
   */
  const ASSERT_COUNT = 'count';

  /**
   * @var string
   */
  const ASSERT_EQUALS = 'is';

  /**
   * @var string
   */
  const ASSERT_NOT_EQUALS = 'is not';

  /**
   * @var string
   */
  const ASSERT_TEXT = 'text';

  /**
   * @var string
   */
  const ASSERT_MATCHES = 'matches';

  /**
   * @var string
   */
  const ASSERT_NOT_MATCHES = 'not matches';

  /**
   * @var string
   */
  const ASSERT_CONTAINS = 'contains';

  /**
   * @var string
   */
  const ASSERT_NOT_CONTAINS = 'not contains';

  /**
   * @var string
   *
   * Indicates the assert will use a callback defined as the assert value.
   */
  const ASSERT_CALLABLE = 'callable';

  protected $needle;

  /**
   * @var string
   */
  private $searchType;

  /**
   * @var mixed
   */
  private $searchValue;

  /**
   * Plugins may provide a callback for casting this to a string.
   *
   * @var callable
   */
  private $toStringOverride;

  /**
   * @var string
   */
  private $assertType;

  /**
   * @var mixed
   */
  private $assertValue;

  /**
   * One of the self::MODIFIER_* values.
   *
   * @var string
   */
  private $modifierType;

  /**
   * @var string
   */
  private $modifierValue;

  /**
   * That which is to be searched.
   *
   * @var array
   */
  private $haystack = [];

  /**
   * @var string
   */
  private $reason = '';

  /**
   * @var string
   */
  private $result = '';

  /**
   * Store the raw assertion array.
   *
   * @var array
   */
  private $definition = [];

  /**
   * @var string
   */
  private $id = '';

  /**
   * Assert constructor.
   *
   * @param array $definition
   *   The raw assert key/value array.
   * @param string $id
   *   An arbitrary value to track this assert by outside consumers.
   */
  public function __construct(array $definition, string $id) {
    $this->definition = $definition;
    $this->id = $id;
  }

  public function getId(): string {
    return $this->id;
  }

  /**
   * Allow direct access to $this->definition, e.g. $this->style
   *
   * @param $key
   *
   * @return mixed|null
   */
  public function __get($key) {
    return $this->definition[$key] ?? NULL;
  }

  /**
   * Set the assert modifier.
   *
   * @param string $modifierValue
   *
   * @return \AKlump\CheckPages\Assert
   *   Self for chaining.
   */
  public function setModifer(string $type, $value): self {
    if (!in_array($type, [
      self::MODIFIER_ATTRIBUTE,
      self::MODIFIER_PROPERTY,
    ])) {
      throw new \InvalidArgumentException("Unrecognized modifier: {$type}");
    }
    $this->modifierType = $type;
    $this->modifierValue = $value;

    return $this;
  }

  /**
   * Return the modifier for this assertion.
   *
   * @return array
   *   - 0 The modifier type.
   *   - 1 The modifier value.
   */
  public function getModifer(): array {
    return [$this->modifierType, $this->modifierValue];
  }

  /**
   * Overwrite the haystack.
   *
   * @param array|Crawler $haystack
   *   The haystack to search.  If the value is a string then simply wrap it as the only element of an indexed array, e.g., `[$string]`.
   *
   * @return $this
   *   Self for chaining.
   */
  public function setHaystack($haystack): self {
    if (!is_array($haystack) && !$haystack instanceof Crawler) {
      throw new \InvalidArgumentException(sprintf('$haystack must be an array or an instance of %s; not an %s', Crawler::class, gettype($haystack)));
    }
    $this->haystack = $haystack;

    return $this;
  }

  /**
   * @return array|Crawler
   */
  public function getHaystack() {
    return $this->haystack;
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
   * Return the search for this assertion.
   *
   * @return array
   *   - 0 The search type.
   *   - 1 The search value.
   */
  public function getSearch(): array {
    return [$this->searchType, $this->searchValue];
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
  public function setAssertion(string $type, $expected): self {
    $this->assertType = $type;
    $this->assertValue = $expected;

    return $this;
  }

  public function getAssertion(): array {
    return [$this->assertType, $this->assertValue];
  }

  /**
   * Run the test assertion.
   *
   * @see \AKlump\CheckPages\Assert::getResult()
   * @see \AKlump\CheckPages\Assert::getReason()
   */
  public function run() {
    if (is_bool($this->result)) {
      return;
    }

    $haystack = $this->haystack;

    // The asserts run against an array, so if $haystack is a Crawler, it must
    // be converted to an array before the asserts are tested.
    if ($haystack instanceof Crawler) {
      if (!$haystack->getNode(0)) {

        // If we are expecting a count of 0, then the fact that the node does
        // not exist is in fact, a pass.
        if ($this->assertType === self::ASSERT_COUNT && empty($this->assertValue)) {
          $this->result = TRUE;

          return;
        }
        $this->reason = sprintf('"%s" does not exist in the DOM.', $this->searchValue);

        $this->result = FALSE;

        return;
      }

      switch ($this->assertType) {
        case self::ASSERT_TEXT:
          $haystack = $haystack->each(function ($node) {
            return trim($node->text());
          });
          break;

        case self::ASSERT_CONTAINS:
        case self::ASSERT_NOT_CONTAINS:
        case self::ASSERT_MATCHES:
        case self::ASSERT_NOT_MATCHES:
        case self::ASSERT_SETTER:
        case self::ASSERT_EQUALS:
        case self::ASSERT_NOT_EQUALS:
          $haystack = $haystack->each(function ($node) {
            if ($this->modifierType === self::MODIFIER_ATTRIBUTE) {
              $value = $node->attr($this->modifierValue);
            }
            else {
              $value = $node->html();
            }

            return trim($value);
          });
          break;
      }
    }

    $pass = NULL;
    switch ($this->assertType) {
      case self::ASSERT_CALLABLE:
        $callback = $this->assertValue;
        try {
          $pass = $callback($this);
          if (TRUE !== $pass) {
            trigger_error(sprintf('The assert type "%s" must return TRUE or throw an exception if the assertion failed.', $this->assertType));
            $pass = FALSE;
          }
        }
        catch (\Exception $exception) {
          $pass = FALSE;
          $this->reason = $exception->getMessage();
        }
        break;

      case self::ASSERT_NOT_CONTAINS:
        foreach ($haystack as $item) {
          $pass = $this->applyCallbackWithVariations($item, function ($item_variation) {
            $this->setNeedle($item_variation);

            return strpos($item_variation, $this->assertValue) === FALSE;
          });
          if (!$pass) {
            $this->reason = sprintf("The following is not supposed to be found:\n\n>>> %s\n\n", $this->assertValue);
            break;
          }
        }
        break;

      case self::ASSERT_CONTAINS:
        foreach ($haystack as $item) {
          $pass = $this->applyCallbackWithVariations($item, function ($item_variation) {
            $this->setNeedle($item_variation);

            return strpos($item_variation, $this->assertValue) !== FALSE;
          });
          if ($pass) {
            break;
          }
        }
        if (!$pass) {
          $this->reason = sprintf("Unable to find:\n\n>>> %s\n\n", $this->assertValue);
        }
        break;

      case self::ASSERT_COUNT:
        $pass = $this->assertCount($this->assertValue, count($haystack));
        break;

      case self::ASSERT_TEXT:
        foreach ($haystack as $item) {
          $pass = $this->applyCallbackWithVariations($item, function ($item_variation) {
            $this->setNeedle($item_variation);

            return $item_variation == $this->assertValue;
          });
          if ($pass) {
            break;
          }
        }
        if (!$pass) {
          $haystack = implode('", "', $haystack);
          $this->reason = sprintf("The actual value\n│\n│   \"%s\"\n│\n│   is not the expected\n│\n│   \"%s\"\n│", $haystack, $this->assertValue);
        }
        break;

      case self::ASSERT_SETTER:
        $this->setNeedle($haystack[0]);
        $pass = TRUE;
        break;

      case self::ASSERT_EQUALS:
        foreach ($haystack as $item) {
          $this->setNeedle($item);
          $pass = $item == $this->assertValue;
          if ($pass) {
            break;
          }
        }
        if (!$pass) {
          $haystack = implode('", "', $haystack);
          $this->reason = sprintf("The actual value\n│\n│   \"%s\"\n│\n│   is not the expected\n│\n│   \"%s\"\n│", $haystack, $this->assertValue);
        }
        break;

      case self::ASSERT_NOT_EQUALS:
        $pass = empty($haystack);
        foreach ($haystack as $item) {
          $this->setNeedle($item);
          $pass = $item != $this->assertValue;
          if ($pass) {
            break;
          }
        }
        if (!$pass) {
          $haystack = implode('", "', $haystack);
          $this->reason = sprintf("The actual value\n│\n│   \"%s\"\n│\n│   should not match exactly to\n│\n│   \"%s\"\n│", $haystack, $this->assertValue);
        }
        break;

      case self::ASSERT_MATCHES:
        foreach ($haystack as $item) {
          $pass = $this->applyCallbackWithVariations($item, function ($item_variation) {
            $this->setNeedle($item_variation);

            return preg_match($this->assertValue, $item_variation);
          });
          if ($pass) {
            break;
          }
        }
        if (!$pass) {
          $this->reason = sprintf("Unable to match actual value \"%s\" using \"%s\".", $item, $this->assertValue);
        }
        break;

      case self::ASSERT_NOT_MATCHES:
        $pass = empty($haystack);
        foreach ($haystack as $item) {
          $pass = $this->applyCallbackWithVariations($item, function ($item_variation) {
            $this->setNeedle($item_variation);

            return !preg_match($this->assertValue, $item_variation);
          });
          if ($pass) {
            break;
          }
        }
        if (!$pass) {
          $this->reason = sprintf("Value \"%s\" should not match RegEx \"%s\".", $item, $this->assertValue);
        }
        break;
    }

    if (is_null($pass)) {
      $this->reason = sprintf('Invalid assertion "%s".', $this->assertType);

      $this->result = FALSE;

      return;
    }

    $this->result = $pass;
  }


  /**
   * Set the needle value.
   *
   * @param $value
   *   The value of the needle
   *
   * @return $this
   *   Self for chaining.
   */
  public function setNeedle($value): self {
    $this->needle = $value;

    return $this;
  }

  /**
   * Return the needle in the haystack used to determine the result.
   *
   * @return mixed
   */
  public function getNeedle() {
    return $this->needle;
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
   * Get the failure result.
   *
   * @return string
   *   The result for failure.
   */
  public function getResult(): string {
    return $this->result;
  }

  /**
   * If this is set the assert run() will be bypassed.
   *
   * @param bool $result
   *   Override the assertion with a set value.
   * @param string $reason
   *   Optional.  Give the reason for overriding the assert.
   *
   * @return \AKlump\CheckPages\Assert
   *   Self for chaining.
   */
  public function setResult(bool $result, string $reason = ''): self {
    $this->result = $result;
    $this->reason = $reason ?: sprintf('The result was set using %s', __METHOD__);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    $prefix = '';
    $suffix = '';
    $modifier = '';
    switch ($this->modifierType) {
      case static::MODIFIER_ATTRIBUTE:
        $modifier = "attribute \"{$this->modifierValue}\" ";
        break;

      case static::MODIFIER_PROPERTY:
        $modifier = "style property \"{$this->modifierValue}\" ";
        break;
    }

    switch ($this->assertType) {
      case static::ASSERT_MATCHES:
        $prefix = sprintf('Match %sagainst RegExp "%s"', $modifier, $this->assertValue);
        break;

      case static::ASSERT_NOT_MATCHES:
        $prefix = sprintf('Do not match %s against RegExp "%s"', $modifier, $this->assertValue);
        break;

      case static::ASSERT_TEXT:
        $prefix = sprintf('Convert %sto plaintext and compare to "%s"', $modifier, $this->assertValue);
        break;

      case static::ASSERT_EQUALS:
      case static::ASSERT_NOT_EQUALS:
        $modifier = $modifier ? "of $modifier" : '';
        $prefix = sprintf('Compare value %sto "%s"', $modifier, $this->assertValue);
        break;

      case static::ASSERT_NOT_CONTAINS:
        $prefix = sprintf('"%s" was not found', $this->assertValue);
        break;

      case static::ASSERT_CONTAINS:
        $modifier = $modifier ? ' in ' . trim($modifier) : '';
        $prefix = sprintf('Find "%s"%s', $this->assertValue, $modifier);
        break;

      case static::ASSERT_COUNT:
        $prefix = 'Count elements';
        break;
    }

    switch ($this->searchType) {
      //      case Style::SEARCH_TYPE:
      case 'style':
        $suffix = sprintf('for the element "%s"', $this->searchValue);
        break;

      case static::SEARCH_ALL:
        $suffix = 'on the page';
        break;

      // TODO This won't work until autoloader is fixed for plugins.
      //      case Xpath::SEARCH_TYPE:
      case 'xpath':
        $suffix = sprintf('after filtering by XPath "%s"', $this->searchValue);
        break;

      // TODO This won't work until autoloader is fixed for plugins.
      //      case static::SEARCH_DOM:
      case 'dom':
        $suffix = sprintf('after selecting with "%s"', $this->searchValue);
        break;

      case 'javascript':
        // TODO This won't work until autoloader is fixed for plugins.
        //      case Javascript::SEARCH_TYPE:
        $suffix = sprintf('after JS evaluation of "%s"', $this->searchValue);
        break;
    }

    $string = ltrim(implode(' ', array_filter([$prefix, $suffix])) . '.', '.');

    // Allow others to modify the string output.
    if (is_callable($this->toStringOverride)) {
      $string = call_user_func($this->toStringOverride, $string, $this);
    }

    return $string;
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
      $pass = $actual === intval($expected);
      if (!$pass) {
        $this->reason = sprintf('Actual count %d is not equal to the expected count of %d.', $actual, $expected);
      }
    }
    else {
      preg_match('/([><=]+)\s*(\d+)/', $expected, $matches);
      list(, $comparator, $expected) = $matches;
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
   * Return the search and assert intersections for a modifier.
   *
   * @param string $modifier
   *   One of the self::MODIFIER_* values.
   *
   * @return array|\string[][]
   *   With keys:
   *   - search array
   *   - assert array
   */
  public function getIntersectionsByModifier(string $modifier): array {
    $intersections = [];
    $intersections[self::MODIFIER_PROPERTY] = [
      'search' => [Style::SEARCH_TYPE],
      'assert' => [
        self::ASSERT_CONTAINS,
        self::ASSERT_EQUALS,
        self::ASSERT_NOT_EQUALS,
        self::ASSERT_MATCHES,
        self::ASSERT_NOT_MATCHES,
      ],
    ];
    $intersections[self::MODIFIER_ATTRIBUTE] = [
      'search' => [self::SEARCH_DOM, Xpath::SEARCH_TYPE],
      'assert' => [
        self::ASSERT_CONTAINS,
        self::ASSERT_EQUALS,
        self::ASSERT_NOT_EQUALS,
        self::ASSERT_MATCHES,
        self::ASSERT_NOT_MATCHES,
      ],
    ];

    return $intersections[$modifier] ?? [];
  }

  /**
   * Return information about available selectors.
   *
   * @return \AKlump\CheckPages\Help[]
   */
  public function getSelectorsInfo(): array {
    return [
      //      new Help(Dom::SEARCH_TYPE, "Select from the DOM using CSS selectors.", [
      //        'p.summary',
      //        'main',
      //        '.story__title',
      //        '\'#edit-submit[value="Create new account"]\'',
      //      ]),
      //            new Help(Style::SEARCH_TYPE, "Select computed styles for an element using CSS selectors.", [
      //        'p.summary',
      //        'main',
      //        '.story__title',
      //        '\'#edit-submit[value="Create new account"]\'',
      //      ]),
      //      new Help(Xpath::SEARCH_TYPE, "Select from the DOM using XPath selectors.", ['(//*[contains(@class, "block-title")])[3]']),
      //            new Help(Javascript::SEARCH_TYPE, "Select the result of a javascript expression", ['location.hash']),
    ];
  }

  /**
   * Return information about modifiers.
   *
   * @return \AKlump\CheckPages\Help[]
   */
  public function getModifiersInfo() {
    return [
      new Help(self::MODIFIER_ATTRIBUTE, "Modify the assert to act against an element's DOM attribute.  Must be combined with `dom` or `xpath`.  Does not work with all assertions.", [
        'id',
        'data-foo',
        'class',
      ]),
      new Help(self::MODIFIER_PROPERTY, "Indicate which style property to assert against.  Used for style searches.", [
        'id',
        'data-foo',
        'class',
      ]),
    ];
  }

  /**
   * Return information about available assertions.
   *
   * @return \AKlump\CheckPages\Help[]
   */
  public function getAssertionsInfo(): array {
    return [
      new Help(self::ASSERT_NOT_CONTAINS, 'Pass if the value is not found in the selection.', ['[token:123]']),
      new Help(self::ASSERT_CONTAINS, 'Pass if the value is found in the selection. Works with `attribute`.', ['foo']),
      new Help(self::ASSERT_COUNT, 'Pass if equal to the number of items in the selection.', [2]),
      new Help(self::ASSERT_EQUALS, "Pass if the selection's markup is equal.  All numeric values, regardless of type are considered equal.  Works with `attribute`.", ['<em>lorem <strong>ipsum dolar</strong> sit amet.</em>']),
      new Help(self::ASSERT_NOT_EQUALS, "Pass if the selection's markup is not equal to the search value exactly.  All numeric values, regardless of type are considered an exact match.  Works with `attribute`.", ['<em>lorem <strong>ipsum dolar</strong> sit amet.</em>']),
      new Help(self::ASSERT_MATCHES, 'Applies a REGEX expression against the selection. Works with `attribute`.', ['/copyright\s+20\d{2}$/']),
      new Help(self::ASSERT_NOT_MATCHES, 'Do not match REGEX expression against the selection. Works with `attribute`.', ['/copyright\s+20\d{2}$/']),
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

  /**
   * Register a callback to use for altering the stringification of this.
   *
   * @param callable $callback
   *   A callback with signature (string, Assert): string.
   *
   * @return $this
   *   Self for chaining.
   */
  public function setToStringOverride(callable $callback): Assert {
    $this->toStringOverride = $callback;

    return $this;
  }

}
