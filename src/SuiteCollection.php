<?php

namespace AKlump\CheckPages;

use AKlump\CheckPages\Parts\Suite;
use Ramsey\Collection\AbstractCollection;

class SuiteCollection extends AbstractCollection {

  public function getType(): string {
    return Suite::class;
  }
}
