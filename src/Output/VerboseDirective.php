<?php

namespace AKlump\CheckPages\Output;

/**
 * Class to validate/parse the --verbose CLI argument value.
 */
final class VerboseDirective {

  private $value;

  /**
   * @param string $value
   *   An empty string is normal verbosity.
   *
   * @see \AKlump\CheckPages\Output\VerboseDirective::validate
   */
  public function __construct(string $value) {
    $this->setValue($value);
    $this->validate();
  }

  private function setValue(string $value): void {
    $value = strtoupper($value);
    $value = str_split($value);
    sort($value);
    $this->value = implode('', $value);
  }

  public function __toString() {
    $value = str_split($this->value);
    sort($value);

    return implode($value);
  }

  /**
   * @param int $verbosity
   *
   * @return static
   *   A new instance created from an integer
   *
   * @see \AKlump\CheckPages\Output\Verbosity
   */
  public static function createFromInt(int $verbosity) {
    $directive = '';
    if ($verbosity & Verbosity::DEBUG) {
      $directive .= 'D';
    }
    if ($verbosity & Verbosity::VERBOSE) {
      $directive .= 'V';
    }
    if ($verbosity & Verbosity::HEADERS) {
      $directive .= 'H';
    }
    if ($verbosity & Verbosity::REQUEST) {
      $directive .= 'S';
    }
    if ($verbosity & Verbosity::RESPONSE) {
      $directive .= 'R';
    }

    return new static($directive);
  }

  /**
   * Get all-intersecting directive.
   *
   * @return string
   *   A directive that will intersect on all methods.  You can use this to
   *   create a new instance for full matching of all messages.
   */
  public static function getTotalIntersection(): string {
    return 'ADV';
  }

  private function validate() {
    // A = same as HSR
    // D = debugging
    // H = headers
    // R = responses
    // S = send (requests)
    // V = verbose
    if (!$this->value) {
      return;
    }
    if (!preg_match('/^(D?H?R?S?V?|A?D?V?)$/', $this)) {
      throw new \InvalidArgumentException(sprintf('Invalid verbose directive "%s"', $this->value));
    }
  }

  public function showVerbose(): bool {
    return preg_match('/V/', $this->value);
  }

  public function showSendHeaders(): bool {
    return preg_match('/A|H(?!R)|HR?S/', $this->value);
  }

  public function showResponseHeaders(): bool {
    return preg_match('/A|H(?![RS])|HR/', $this->value);
  }

  public function showSendBody(): bool {
    return preg_match('/A|S/', $this->value);
  }

  public function showResponseBody(): bool {
    return preg_match('/A|R/', $this->value);
  }

  public function showDebugging(): bool {
    return preg_match('/D/', $this->value);
  }

  /**
   * Determine if another object shares any same directive.
   *
   * @param \AKlump\CheckPages\Output\VerboseDirective $directive
   *
   * @return bool
   *   True if any of the verbose attributes are the same.
   */
  public function intersectsWith(VerboseDirective $directive): bool {
    if (strval($this) === strval($directive)) {
      return TRUE;
    }
    if ($this->showDebugging() && $directive->showDebugging()) {
      return TRUE;
    }
    if ($this->showResponseBody() && $directive->showResponseBody()) {
      return TRUE;
    }
    if ($this->showResponseHeaders() && $directive->showResponseHeaders()) {
      return TRUE;
    }
    if ($this->showSendBody() && $directive->showSendBody()) {
      return TRUE;
    }
    if ($this->showSendHeaders() && $directive->showSendHeaders()) {
      return TRUE;
    }
    if ($this->showVerbose() && $directive->showVerbose()) {
      return TRUE;
    }

    return FALSE;
  }

}
