<?php

namespace AKlump\CheckPages\Output;

interface ResultsToFileInterface {

  /**
   * Create a new instance.
   *
   * @param string $output_filename
   *   The filename to write to.
   */
  public static function output(string $output_filename);

}
