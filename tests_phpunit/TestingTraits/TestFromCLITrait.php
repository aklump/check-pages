<?php

namespace AKlump\CheckPages\Tests\TestingTraits;


/**
 * Provides functionality to execute CLI commands for testing purposes.
 */
trait TestFromCLITrait {

  use TestWithFilesTrait;

  protected int $testFromCLITraitExitCode;

  /**
   * @param string $args As per the CLI, e.g. "--filter=users -vvv"
   *
   * @return string[] The command output.
   */
  protected function runFromCommandLine(string $args): array {
    $check_pages_bin = realpath(ROOT . '/checkpages');
    $fixtures_dir = TESTS_DIR . '/fixtures/cli/';
    $runner = $fixtures_dir . 'runner.php';
    $command = PHP_BINARY . " $check_pages_bin run $runner --dir=$fixtures_dir $args";
    $exit_code = NULL;
    exec($command, $output, $exit_code);
    $this->testFromCLITraitExitCode = (int) $exit_code;

    return $output;
  }

  protected function getCommandLineExitCode(): int {
    return $this->testFromCLITraitExitCode;
  }

}
