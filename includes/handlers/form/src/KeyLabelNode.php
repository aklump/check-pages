<?php

namespace AKlump\CheckPages\Handlers\Form;

final class KeyLabelNode implements \Stringable {

  private string $key;

  private $value;

  /**
   * @param string $key
   * @param $value
   */
  public function __construct(string $key, $value) {
    $this->key = $key;
    $this->value = $value;
  }


  /**
   * @return mixed
   */
  public function getKey() {
    return $this->key;
  }

  /**
   * @return mixed
   */
  public function getLabel() {
    return $this->value;
  }

  public function mutateToKey(string &$value): bool {
    if ($value === $this->key) {
      $value = $this->key;
      return true;
    }
    elseif ($value === $this->value) {
      $value = $this->key;
      return true;
    }

    return FALSE;
  }

  public function __toString() {
    return sprintf('%s|%s', $this->key, $this->value);
  }
}
