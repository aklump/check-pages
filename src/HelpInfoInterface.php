<?php

namespace AKlump\CheckPages;

interface HelpInfoInterface {

  /**
   * The name of the class constant.
   *
   * @return string
   */
  public function constant(): string;

  /**
   * The code to use in assertions.
   *
   * @return string
   */
  public function code(): string;

  public function description(): string;

  public function examples(): array;

}
