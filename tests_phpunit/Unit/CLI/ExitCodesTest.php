<?php

namespace AKlump\CheckPages\Tests\Unit\CLI;

use AKlump\CheckPages\Tests\TestingTraits\TestFromCLITrait;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class ExitCodesTest extends TestCase {

  use TestFromCLITrait;

  public function testBogusFilterReturns1AndReportsNoTestsNoAssertionsNoFailures() {
    $output = $this->runFromCommandLine('--filter=bogus');
    $output = implode(PHP_EOL, $output);
    $this->assertStringContainsString('Tests: 0, Assertions: 0, Failures: 0', $output);
    $this->assertSame(1, $this->getCommandLineExitCode());
  }


}
