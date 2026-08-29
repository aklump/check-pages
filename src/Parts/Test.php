<?php

namespace AKlump\CheckPages\Parts;

use AKlump\CheckPages\Interfaces\HasConfigInterface;
use AKlump\CheckPages\Output\MessageDelivery;
use AKlump\CheckPages\TAP\Directive;
use AKlump\CheckPages\TAP\Result;
use AKlump\CheckPages\TAP\TestInterface;
use AKlump\CheckPages\Traits\HasConfigTrait;
use AKlump\CheckPages\Traits\HasRunnerTrait;
use AKlump\CheckPages\Traits\PassFailTrait;
use AKlump\CheckPages\Traits\TAPTrait;
use AKlump\CheckPages\Variables;
use AKlump\CheckPages\Messaging\HasMessagesInterface;
use AKlump\CheckPages\Messaging\HasMessagesTrait;
use JsonSerializable;

class Test implements JsonSerializable, PartInterface, HasConfigInterface, HasMessagesInterface, TestInterface {

  use HasRunnerTrait;
  use HasMessagesTrait;
  use TAPTrait;
  use PassFailTrait;
  use HasConfigTrait {
    HasConfigTrait::setConfig as traitSetConfig;
  }

  const PASSED = 'P';

  const FAILED = 'F';

  const SKIPPED = 'S';

  const PENDING = '?';

  /**
   * @var string
   */
  protected $title = '';

  protected $results = [];

  protected $badges = [];

  /**
   * @var string|null
   */
  private ?string $description = NULL;

  /**
   * @var \AKlump\CheckPages\TAP\Directive|null
   */
  private ?Directive $directive = NULL;

  /**
   * @var \AKlump\CheckPages\TAP\Result|null
   */
  private ?Result $result = NULL;

  /**
   * @var \AKlump\CheckPages\Variables
   */
  protected $vars;

  /**
   * @var \AKlump\CheckPages\Parts\Suite
   */
  private $suite;

  private int $number;

  public function __construct(int $number, array $config, Suite $suite) {
    $this->suite = $suite;
    $this->setRunner($suite->getRunner());
    $this->setNumber($number);
    $this->setConfig($config);
    $this->vars = new Variables();
  }

  /**
   * Sugar coating for getSuite()->interpolate().
   *
   * @param $value
   *
   * @return void
   */
  public function interpolate(&$value): void {
    $this->getSuite()->interpolate($value);
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
    if ($this->description !== NULL) {
      $title = $this->description;
    }
    else {
      $config = $this->getConfig();
      $title = trim($config['why'] ?? '');
      if (!$title) {
        $url = $this->getRelativeUrl();
        $method = $this->getHttpMethod();
        $has_multiple_methods = count($this->getSuite()->getHttpMethods()) > 1;
        $method = $has_multiple_methods ? $method : '';
        if ($url) {
          $title = ltrim("$method $url", ' ');
        }
      }
    }
    if ($title) {
      $this->interpolate($title);
    }

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
   * @return void
   */
  public function addBadge(string $badge): void {
    $this->badges[] = $badge;
    $this->badges = array_unique($this->badges);
  }

  public function setConfig(array $config): void {
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
    $this->traitSetConfig($config);
  }

  public function id(): string {
    return $this->getNumber();
  }

  /**
   * Return the relative URL being tested.
   *
   * @return string
   *   The relative test URL.  To get the absolute url, you need to do like
   *   this: $this->getRunner()->withBaseUrl($this->getRelativeUrl()).
   */
  public function getRelativeUrl(): string {
    $url = $this->get('url') ?? '';

    return $this->getRunner()->withoutBaseUrl($url);
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
    return $this->getSuite() . '/' . $this->id();
  }

  public function echoMessages() {
    $messages = $this->getMessages();
    $delivery = new MessageDelivery();
    $delivery($this->getRunner()->getMessenger(), $messages);
    $this->setMessages([]);
  }

  public function getDirective(): Directive {
    return $this->directive ?? Directive::NONE;
  }

  public function getNumber(): int {
    if ($this->number !== NULL) {
      return $this->number;
    }
    if (is_numeric($this->id)) {
      return (int) $this->id;
    }

    return 0;
  }

  public function getResult(): Result {
    return $this->result ?? Result::NONE;
  }

  public function setDescription(string $description): TestInterface {
    $this->description = $description;

    return $this;
  }

  public function setNumber(int $number): TestInterface {
    $this->number = $number;

    return $this;
  }

  public function setResult(Result $result): TestInterface {
    $this->result = $result;

    return $this;
  }
}
