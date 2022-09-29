<?php

namespace AKlump\CheckPages;

/**
 * Manages test variables.
 */
final class Variables implements \Countable {

  private $values = [];

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
        $interpolated = str_replace('${' . $k . '}', $v, $value);
        if ($value != $interpolated) {
          if (strcmp($interpolated, $v) === 0) {
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
  public function count() {
    return count($this->values);
  }
}
