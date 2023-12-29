<?php

namespace AKlump\CheckPages\Parts;

use AKlump\CheckPages\Interfaces\HasConfigInterface;
use AKlump\CheckPages\Output\MessageDelivery;
use AKlump\CheckPages\Traits\HasConfigTrait;
use AKlump\CheckPages\Traits\HasRunnerTrait;
use AKlump\CheckPages\Traits\PassFailTrait;
use AKlump\CheckPages\Traits\SkipTrait;
use AKlump\CheckPages\Variables;
use AKlump\Messaging\HasMessagesInterface;
use AKlump\Messaging\HasMessagesTrait;
use AKlump\Messaging\Processor;
use JsonSerializable;

class Test implements JsonSerializable, PartInterface, HasConfigInterface, HasMessagesInterface {

  use HasRunnerTrait;
  use HasMessagesTrait;
  use PassFailTrait;
  use SkipTrait;
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
   * @var \AKlump\CheckPages\Variables
   */
  protected $vars;

  /**
   * @var \AKlump\CheckPages\Parts\Suite
   */
  private $suite;

  /**
   * @var string
   */
  private $id;

  public function __construct(string $id, array $config, Suite $suite) {
    $this->suite = $suite;
    $this->setRunner($suite->getRunner());
    $this->id = $id;
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

}
