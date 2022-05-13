<?php

namespace AKlump\CheckPages\Parts;

use AKlump\CheckPages\Output\FeedbackInterface;

class Test implements \JsonSerializable, FeedbackInterface {

  const PASSED = 'P';

  const FAILED = 'F';

  const IS_COMPLETE = 'C';

  protected $results = [];

  protected $failed = FALSE;

  public function __construct(string $id, array $config, Suite $suite) {
    $this->suite = $suite;
    $this->id = $id;
    $this->setConfig($config);
  }

  /**
   * Writes a message to the output and adds a newline at the end.
   *
   * @param string|iterable $messages The message as an iterable of strings or a single string
   * @param int $options A bitmask of options (one of the OUTPUT or VERBOSITY constants), 0 is considered the same as self::OUTPUT_NORMAL | self::VERBOSITY_NORMAL
   */
  public function writeln($messages, int $options = 0) {
    return $this->getRunner()->getOutput()->writeln($messages, $options);
  }

  /**
   * Writes a message to the output.
   *
   * @param string|iterable $messages The message as an iterable of strings or a single string
   * @param bool $newline Whether to add a newline
   * @param int $options A bitmask of options (one of the OUTPUT or VERBOSITY constants), 0 is considered the same as self::OUTPUT_NORMAL | self::VERBOSITY_NORMAL
   */
  public function write($messages, bool $newline = FALSE, int $options = 0) {
    return $this->getRunner()
      ->getOutput()
      ->write($messages, $newline, $options);
  }

  /**
   * Get the description of the test.
   *
   * This may be the test's `why` or a combination of method and url, depending
   * upon context.
   *
   * @return string
   *   The test description.
   */
  public function getDescription(): string {
    $config = $this->getConfig();
    $description = trim($config['why'] ?? '');

    if (!$description) {
      $url = $this->getRelativeUrl();
      $method = $this->getHttpMethod();
      $has_multiple_methods = count($this->getSuite()->getHttpMethods()) > 1;
      $method = $has_multiple_methods ? $method : '';
      $description = ltrim("$method $url", ' ');
    }

    return $description;
  }

  /**
   * @param bool $failed
   *
   * @return
   *   Self for chaining.
   */
  public function setFailed(): self {
    $this->failed = TRUE;

    return $this;
  }

  /**
   * @param bool $failed
   *
   * @return
   *   Self for chaining.
   */
  public function setPassed(): self {
    $this->failed = FALSE;

    return $this;
  }

  public function hasFailed(): bool {
    return $this->failed;
  }

  public function hasPassed(): bool {
    return $this->failed === FALSE;
  }

  /**
   * @return array
   */
  public function getResults(): array {
    return $this->results;
  }

  /**
   * @param array $results
   *   Each element of the array is an array with the keys: data, level.
   *
   * @return
   *   Self for chaining.
   *
   * @see \AKlump\CheckPages\Parts\Runner::info()
   */
  public function setResults(array $results): self {
    $this->results = $results;

    return $this;
  }

  public function setConfig(array $config): self {
    // Normalize config keys.
    $keys = array_map(function ($key) {
      return $key === 'visit' ? 'url' : $key;
    }, array_keys($config));
    $config = array_combine($keys, $config);

    $this->config = $config;

    return $this;
  }

  public function getConfig(): array {
    return $this->config;
  }

  public function id(): string {
    return $this->id;
  }

  /**
   * Return the relative URL being tested.
   *
   * @return string
   *   The relative test URL.  To get the absolute url, you need to do like
   *   this: $this->getRunner()->url($this->getRelativeUrl()).
   */
  public function getRelativeUrl(): string {
    return $this->config['url'] ?? '';
  }

  /**
   * Return the relative URL being tested.
   *
   * @return string
   *   The relative test URL.  To get the absolute url, you need to do like
   *   this: $this->getRunner()->url($this->getRelativeUrl()).
   */
  public function getAbsoluteUrl(): string {
    $relative = $this->getRelativeUrl();
    if (empty($relative)) {
      return '';
    }

    return $this->getRunner()->url($relative);
  }

  public function getRunner(): Runner {
    return $this->suite->getRunner();
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
    return strtoupper($this->config['request']['method'] ?? 'get');
  }

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    return $this->getConfig();
  }

}
