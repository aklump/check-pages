<?php


namespace AKlump\CheckPages;


interface StorageInterface {

  /**
   * @param $key
   * @param null $default
   *
   * @return mixed
   */
  public function get($key, $default = NULL);

  /**
   * @param $key
   * @param $value
   *
   * @return $this
   */
  public function set($key, $value);
}
