<?php

namespace AKlump\CheckPages\Tests\Unit\Parts;

use AKlump\CheckPages\Files\LocalFilesProvider;
use AKlump\CheckPages\Parts\Runner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @group default
 * @covers \AKlump\CheckPages\Parts\Runner::writeToFile
 * @uses \AKlump\CheckPages\Parts\Runner
 * @uses \AKlump\CheckPages\Files\LocalFilesProvider
 */
final class RunnerWriteToFileTest extends TestCase {

  private string $testDir;

  protected function setUp(): void {
    $this->testDir = sys_get_temp_dir() . '/check_pages_test_' . uniqid();
    mkdir($this->testDir);
  }

  protected function tearDown(): void {
    $this->removeDir($this->testDir);
  }

  private function removeDir(string $dir): void {
    if (!is_dir($dir)) {
      return;
    }
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
      $path = "$dir/$file";
      is_dir($path) ? $this->removeDir($path) : unlink($path);
    }
    rmdir($dir);
  }

  public function testWriteToFileAppendMode() {
    $runner = $this->getRunner($this->testDir);
    $filename = 'append.txt';

    $path1 = $runner->writeToFile($filename, ['line 1'], 'a');
    $path2 = $runner->writeToFile($filename, ['line 2'], 'a');

    $this->assertNotEmpty($path1, 'Path 1 should not be empty');
    $this->assertSame($path1, $path2);
    $this->assertFileExists($path1);
    $content = file_get_contents($path1);
    $this->assertEquals("line 1" . PHP_EOL . "line 2" . PHP_EOL, $content);
  }

  public function testWriteToFileOverwriteMode() {
    $runner = $this->getRunner($this->testDir);
    $filename = 'overwrite.txt';

    $runner->writeToFile($filename, ['first write'], 'w+');
    $path = $runner->writeToFile($filename, ['second write'], 'w+');

    $this->assertFileExists($path);
    $content = file_get_contents($path);
    $this->assertEquals("second write" . PHP_EOL, $content);
  }

  public function testWriteToManyUniqueFiles() {
    $runner = $this->getRunner($this->testDir);
    $count = 10;
    $paths = [];
    for ($i = 0; $i < $count; $i++) {
      $filename = "file_$i.txt";
      $paths[$i] = $runner->writeToFile($filename, ["content $i"]);
    }

    foreach ($paths as $i => $path) {
      $this->assertFileExists($path);
      $this->assertEquals("content $i" . PHP_EOL, file_get_contents($path));
    }
  }

  private function getRunner(string $logDir): Runner {
    $input = $this->createMock(InputInterface::class);
    $output = $this->createMock(OutputInterface::class);
    $runner = new Runner($input, $output);
    
    // Runner needs a 'files' provider (root FilesProvider)
    $root_files = new LocalFilesProvider(dirname($logDir));
    $runner->setFiles($root_files);

    // We need to set 'files' in config BEFORE getLogFiles() is called.
    $runner->setConfig([
      'files' => basename($logDir),
      'id' => 'test_runner',
    ]);

    return $runner;
  }
}
