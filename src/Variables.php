<?php

namespace AKlump\CheckPages;

/**
 * Manages test variables.
 */
final class Variables implements \Countable, \JsonSerializable, \Iterator {

  private $values = [];

  private $currentKey = 0;

  public function setItem(string $key, $value): self {
    if (is_null($value) || is_scalar($value)) {
      $this->values[$key] = $value;

      return $this;
    }
    foreach ($value as $k => $v) {
      if (is_numeric($k)) {
        $this->setItem($key . "[$k]", $v);
      }
      $this->setItem($key . ".$k", $v);
    }

    return $this;
  }

  public function getItem(string $key) {
    return $this->values[$key] ?? NULL;
  }

  public function removeItem(string $key): self {
    unset($this->values[$key]);

    return $this;
  }

  /**
   * Check for the presence of un-interpolated tokens.
   *
   * @param $value
   *   A value as would be sent to ::interpolate.
   *
   * @return bool
   *   True if there are any tokens remaining in $value.  This can happen after
   *   calling interpolate, if the values are not set for certain tokens.
   */
  public function needsInterpolation($value): bool {
    if (is_string($value)) {
      return strstr($value, '${') !== FALSE;
    }
    if (is_array($value)) {
      foreach ($value as $v) {
        if ($this->needsInterpolation($v)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Recursively interpolate using instance variables.
   *
   * @param string|array &$value
   *   A string or array of strings to be interpolated.  Interpolation will only
   *   occur if the variable has been set--tokens that point to unset variables
   *   will be left untouched.
   */
  public function interpolate(&$value, &$context = NULL): void {
    if (!is_array($value)) {

      // We can only interpolate in strings, because otherwise they would not
      // contain the ${}, also the interpolation casts to a string, so we want
      // to avoid variable type change by accident.
      if (!is_string($value)) {
        return;
      }

      // Create a per-recursion set of values.  This should not be a class
      // variable because it should only persist for the duration of a single
      // call, and it's child recursions.
      if (!isset($context['find'])) {
        $context['find'] = array_map(function ($key) {
          return "\\\$\{$key\}";
        }, array_keys($this->values));
      }
      foreach ($this->values as $k => $v) {
        $interpolated = $value;
        $changed_by_interpolation = FALSE;
        if (is_string($value)) {
          $interpolated = str_replace('${' . $k . '}', (string) $v, $value);
          $changed_by_interpolation = $value != $interpolated;
        }
        if ($changed_by_interpolation) {
          if (is_string($interpolated) && strcmp($interpolated, (string) $v) === 0) {
            $value = $v;
          }
          else {
            $value = $interpolated;
          }
        }
      }

      return;
    }
    foreach ($value as $k => $v) {
      // Interpolate the key, then the value, then combine.
      unset($value[$k]);
      $this->interpolate($k, $context);
      $this->interpolate($v, $context);
      $value[$k] = $v;
    }
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function count() {
    return count($this->values);
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function jsonSerialize() {
    $data = [];
    foreach ($this->values as $key => $value) {
      $data[$key] = $value;
    }

    return $data;
  }

  #[\ReturnTypeWillChange]
  public function current() {
    return $this->values[$this->key()] ?? NULL;
  }

  #[\ReturnTypeWillChange]
  public function next() {
    ++$this->currentKey;
  }

  #[\ReturnTypeWillChange]
  public function key() {
    $keys = array_keys($this->values);

    return $keys[$this->currentKey];
  }

  #[\ReturnTypeWillChange]
  public function valid() {
    $keys = array_keys($this->values);

    return array_key_exists($this->currentKey, $keys);
  }

  #[\ReturnTypeWillChange]
  public function rewind() {
    $this->currentKey = 0;
  }

}
