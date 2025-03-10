<?php

namespace AKlump\CheckPages\Tests\Unit\CLI;

use AKlump\CheckPages\Tests\Unit\Traits\TestFromCLITrait;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class ExitCodesTest extends TestCase {

  use TestFromCLITrait;

  public function testBogusFilterReturns1() {
    $this->runFromCommandLine('--filter=bogus');
    $this->assertSame(1, $this->getCommandLineExitCode());
  }


}
