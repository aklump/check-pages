<?php

namespace AKlump\CheckPages\Service;

use AKlump\CheckPages\Assert;
use AKlump\CheckPages\Browser\GuzzleDriver;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\AssertEvent;
use AKlump\CheckPages\Interfaces\HasConfigInterface;
use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\Handlers\Count;
use AKlump\CheckPages\Handlers\Dom;
use AKlump\CheckPages\Handlers\Value;
use AKlump\CheckPages\Handlers\Xpath;
use AKlump\CheckPages\Traits\HasConfigTrait;
use AKlump\CheckPages\Traits\PassFailTrait;
use AKlump\CheckPages\Variables;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DomCrawler\Crawler;

/**
 * A stand-alone assertion class.
 *
 * This differs from \AKlump\CheckPages\Assert in that it can be used without
 * the need for test or runner objects.
 *
 * @code
 * Assertion::create([
 *   'matches' => '/cow/'
 * ])
 * ->runAgainst('Moscow');
 *
 * or using instantiation...
 *
 * $obj = new Assertion([
 *   'matches' => '/cow/'
 * ]);
 * $obj->setHaystack()->run();
 * if ($obj->hasPassed()) { ...
 * @endcode
 */
class Assertion implements HasConfigInterface {

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  private $dispatcher;

  /**
   * @var \AKlump\CheckPages\Parts\Runner
   */
  private $runner;

  /**
   * @var \AKlump\CheckPages\Parts\Test
   */
  private $test;

  /**
   * @var \AKlump\CheckPages\Browser\GuzzleDriver
   */
  private $driver;

  /**
   * @var \AKlump\CheckPages\Assert
   */
  private $assert;

  /**
   * @var \AKlump\CheckPages\Variables
   */
  private $vars;

  /**
   * @var false
   */
  private $mayRun;

  use PassFailTrait;
  use HasConfigTrait;

  /**
   * Only use this if you are trying to avoid the default listener classes.
   *
   * @param array $config
   * @param array|null $listener_classes
   *   Do not pass this unless you are wanting to override the default plugins.
   *
   * @see \AKlump\CheckPages\Service\Assertion::create()
   */
  public function __construct(array $config, array $listener_classes = NULL) {
    $this->setConfig($config);
    $this->dispatcher = DispatcherFactory::create();
    if (is_null($listener_classes)) {
      $listener_classes = [
        // Not all plugins work without full context (runner, test, driver), so we
        // have hand-picked a selection here that will.
        Count::class,
        Dom::class,
        Value::class,
        Xpath::class,
      ];
    }
    if ($listener_classes) {
      foreach ($listener_classes as $classname) {
        if (class_exists($classname)) {
          DispatcherFactory::addSubscribedEvents($this->dispatcher, $classname::getSubscribedEvents());
        }
      }
    }

    // Have to create a bunch of instances just to satisfy legacy code.
    // Most of these things are not actually used and could be refactored in the
    // future.
    $runner = new Runner(new ArrayInput([]), new NullOutput());
    $suite = new Suite('', $runner);
    $this->test = new Test('', [], $suite);
    $this->driver = new GuzzleDriver();
    $this->assert = new Assert('', $config, $this->test);
    $this->assert->setSearch(Assert::SEARCH_ALL);
    $this->vars = new Variables();

    // Flag to only allow ::run() with proper setup, and only once.
    $this->mayRun = FALSE;
  }

  public function __toString() {
    return json_encode($this->getConfig());
  }

  /**
   * Entry point for static use, see class comments for code example.
   *
   * @param array $config
   *   The assertion configuration array.
   *
   * @return static
   */
  public static function create(array $config): self {
    return new static($config);
  }

  /**
   * @param string|array|\Symfony\Component\DomCrawler\Crawler $haystack
   *
   * @return bool
   *   True if the assertion passed.
   */
  public function runAgainst($haystack): bool {
    return $this->setHaystack($haystack)->run()->hasPassed();
  }

  /**
   * Set the haystack
   *
   * @param string|array|\Symfony\Component\DomCrawler\Crawler $haystack
   *
   * @return $this
   *   Self for chaining.
   *
   * @see Assertion::runAgainst()
   */
  public function setHaystack($haystack): self {
    if (!is_array($haystack) && !$haystack instanceof Crawler) {
      $haystack = [$haystack];
    }
    $this->assert->setHaystack($haystack);
    $this->mayRun = TRUE;

    return $this;
  }

  public function run(): self {
    if (FALSE === $this->mayRun) {
      throw new \RuntimeException('This instance is has not been configured to run yet.');
    }

    $this->handleModifier();
    $this->handleSet();
    $this->handleCoreAssertions();

    $event = new AssertEvent($this->assert, $this->test, $this->driver);
    $this->dispatcher->dispatch($event, Event::ASSERT_CREATED);
    $this->assert->run();
    $this->dispatcher->dispatch($event, Event::ASSERT_FINISHED);

    if ($this->assert->hasPassed()) {
      $this->setPassed();
    }
    if ($this->assert->hasFailed()) {
      $this->setFailed();
    }

    $this->handleSetValue();

    $this->mayRun = FALSE;

    return $this;
  }

  private function handleCoreAssertions() {
    $assertions = array_map(function ($help) {
      return $help->code();
    }, $this->assert->getAssertionsInfo());
    $config = $this->assert->getConfig();
    foreach ($assertions as $code) {
      if (isset($config[$code])) {
        $this->assert->setAssertion($code, $config[$code]);
        break;
      }
    }
  }

  private function handleModifier() {
    $config = $this->assert->getConfig();
    if (!empty($config[Assert::MODIFIER_ATTRIBUTE])) {
      $this->assert->setModifer(Assert::MODIFIER_ATTRIBUTE, $config[Assert::MODIFIER_ATTRIBUTE]);
    }
  }

  private function handleSet() {
    if ($this->assert->get(Assert::ASSERT_SETTER)) {
      // This may be overridden below if there is more going on than just `set`,
      // and that's totally fine and the way it should be.  However if only
      // setting, we need to know that later own in the flow.
      $this->assert->setAssertion(Assert::ASSERT_SETTER, NULL);
    }
  }

  private function handleSetValue() {
    if ($this->hasPassed() && $this->assert->get('set')) {
      $this->vars->setItem($this->assert->get('set'), $this->assert->getNeedle());
    }
  }

}
