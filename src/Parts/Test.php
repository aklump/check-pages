<?php

namespace AKlump\CheckPages\Parts;

class Test implements \JsonSerializable {

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

  /**
   * @return array
   */
  public function getResults(): array {
    return $this->results;
  }

  /**
   * @param array $results
   *   Self for chaining.
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
