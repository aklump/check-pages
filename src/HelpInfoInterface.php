<?php

namespace AKlump\CheckPages;

interface HelpInfoInterface {

  /**
   * The (machine) code to use when writing assertions.
   *
   * @return string
   */
  public function code(): string;

  public function description(): string;

  public function examples(): array;

}
