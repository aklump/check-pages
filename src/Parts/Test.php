<?php

namespace AKlump\CheckPages\Parts;

use AKlump\CheckPages\Traits\HasConfigTrait;
use AKlump\CheckPages\Traits\HasRunnerTrait;
use AKlump\CheckPages\Traits\PassFailTrait;
use AKlump\CheckPages\Variables;
use AKlump\Messaging\HasMessagesTrait;
use AKlump\Messaging\Processor;

class Test implements \JsonSerializable, PartInterface {

  use HasRunnerTrait;
  use HasMessagesTrait;
  use PassFailTrait;
  use HasConfigTrait {
    HasConfigTrait::setConfig as traitSetTrait;
  }

  const PASSED = 'P';

  const FAILED = 'F';

  const IS_COMPLETE = 'C';

  /**
   * @var string
   */
  protected $title = '';

  protected $results = [];

  protected $badges = [];

  /**
   * @var \AKlump\CheckPages\Parts\Variables
   */
  protected $vars;

  public function __construct(string $id, array $config, Suite $suite) {
    $this->suite = $suite;
    $this->setRunner($suite->getRunner());
    $this->id = $id;
    $this->setConfig($config);
    $this->vars = new Variables();
  }

  /**
   * Get the test-scoped variables.
   *
   * @return \AKlump\CheckPages\Variables
   */
  public function variables(): Variables {
    return $this->vars;
  }

  /**
   * Interpolate variables of all scopes on a value.
   *
   * If you wish to only apply test-scoped variables do like so:
   *
   * @code
   * $this->variables()->interpolate($value)
   * @endcode
   *
   * @param $value
   *
   * @return void
   */
  public function interpolate(&$value): void {
    $this->variables()->interpolate($value);
    $this->getSuite()->variables()->interpolate($value);
  }

  /**
   * Get the description of the test.
   *
   * This may be the test's `why` or a combination of method and url, depending
   * upon context.  It will be interpolated at the time of the call.
   *
   * @return string
   *   The test description.
   */
  public function getDescription(): string {
    $config = $this->getConfig();
    $title = trim($config['why'] ?? '');
    if (!$title) {
      $url = $this->getRelativeUrl();
      $method = $this->getHttpMethod();
      $has_multiple_methods = count($this->getSuite()->getHttpMethods()) > 1;
      $method = $has_multiple_methods ? $method : '';
      $title = ltrim("$method $url", ' ');
    }
    $this->interpolate($title);

    return $title . rtrim(' ' . (implode('', $this->badges)));
  }

  /**
   * Add a badge to a test for distinguishing special cases.
   *
   * For example, authenticated, javascript, session-started, etc.
   *
   * @param string $badge
   *   Usually an emoji, but may be combined with (colored) text as appropriate.
   *
   * @return $this
   *   Self for chaining.
   */
  public function addBadge(string $badge) {
    $this->badges[] = $badge;
    $this->badges = array_unique($this->badges);

    return $this;
  }

  public function setConfig(array $config): self {
    unset($this->title);

    // Cast find to an array if needed.
    if (isset($config['find']) && !is_array($config['find'])) {
      $config['find'] = [$config['find']];
    }

    // Normalize config keys.
    $keys = array_map(function ($key) {
      return $key === 'visit' ? 'url' : $key;
    }, array_keys($config));
    $config = array_combine($keys, $config);
    $this->traitSetTrait($config);

    return $this;
  }

  public function id(): string {
    return $this->id;
  }

  /**
   * Return the relative URL being tested.
   *
   * @return string
   *   The relative test URL.  To get the absolute url, you need to do like
   *   this: $this->getRunner()->withBaseUrl($this->getRelativeUrl()).
   */
  public function getRelativeUrl(): string {
    return $this->getRunner()->withoutBaseUrl($this->get('url'));
  }

  /**
   * Return the relative URL being tested.
   *
   * @return string
   *   The relative test URL.  To get the absolute url, you need to do like
   *   this: $this->getRunner()->withBaseUrl($this->getRelativeUrl()).
   */
  public function getAbsoluteUrl(): string {

    // TODO Rewrite with BaseUrlTrait?
    $relative = $this->getRelativeUrl();
    if (empty($relative)) {
      return '';
    }

    return $this->getRunner()->withBaseUrl($relative);
  }

  public function getSuite(): Suite {
    return $this->suite;
  }

  /**
   * Get the HTTP method for this test.
   *
   * @return string
   *   The HTTP method used by the test, e.g. GET, PUT, POST, etc.
   */
  public function getHttpMethod(): string {
    $request = $this->get('request');
    if ($request) {
      $method = $request['method'] ?? NULL;
    }

    return strtoupper($method ?? 'get');
  }

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    return $this->getConfig();
  }

  public function __toString() {
    return $this->getSuite() . '\\' . $this->id();
  }

  public function echoMessages() {
    $messenger = $this->getRunner()->getMessenger()
      ->addProcessor([Processor::class, 'wordWrap'])
      ->addProcessor([Processor::class, 'tree']);
    foreach ($this->getMessages() as $message) {
      $messenger->deliver($message);
    }
    $this->setMessages([]);
  }
}
