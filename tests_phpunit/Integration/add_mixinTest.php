<?php

use AKlump\CheckPages\Exceptions\StopRunnerException;
use AKlump\CheckPages\Files\LocalFilesProvider;
use AKlump\CheckPages\Helpers\BuildContainer;
use AKlump\CheckPages\Parts\Runner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @covers ::add_mixin
 * @uses \AKlump\CheckPages\Exceptions\StopRunnerException
 * @uses \AKlump\CheckPages\Files\LocalFilesProvider
 * @uses \AKlump\CheckPages\Helpers\AssertRunSuiteNotCalled
 * @uses \AKlump\CheckPages\Helpers\BuildContainer
 * @uses \AKlump\CheckPages\Parts\Runner
 * @uses \AKlump\CheckPages\Plugin\HandlersManager
 * @uses \AKlump\CheckPages\Exceptions\UnresolvablePathException
 */
class add_mixinTest extends TestCase {

  private Runner $runner;

  public function testPrependsMixinDirectoryIfNeeded() {
    global $container;
    add_mixin('bar_mixin');
    $path = $container->get('defined_vars')->path;
    $this->assertStringEndsWith('mixins/bar_mixin.php', $path);
  }

  public function testAssertCannotCallWhenRunSuiteStateIsTrue() {
    $this->runner::setState('run_suite_called', TRUE);
    $this->expectException(\RuntimeException::class);
    add_mixin('foo_mixin');
  }

  public function testFunctionAddsExceptionPrefixToThrownException() {
    try {
      add_mixin('throws_mixin');
    }
    catch (StopRunnerException $exception) {
      $this->assertStringContainsString('The mixin "throws_mixin" has failed.', $exception->getMessage());
      $this->assertStringContainsString('Testing failed due to an unspecified error', $exception->getMessage());
    }
  }

  public function testAddMixinAddsExpectedVariablesToContainer() {
    global $container;
    $mixin_config = ['foo' => 'bar'];
    add_mixin('foo_mixin', $mixin_config);

    $defined_vars = (array) $container->get('defined_vars');
    $this->assertCount(4, $defined_vars);
    $this->assertSame('foo_mixin.php', basename($defined_vars['path']));
    $this->assertSame($container, $defined_vars['container']);
    $this->assertSame($this->runner, $defined_vars['runner']);
    $this->assertSame($mixin_config, $defined_vars['mixin_config']);
  }

  protected function setUp(): void {
    global $container;
    $root_files = new LocalFilesProvider(ROOT);
    $container = (new BuildContainer($root_files))();
    $input = $this->getMockBuilder(InputInterface::class)
      ->getMock();
    $output = $this->getMockBuilder(OutputInterface::class)
      ->getMock();
    $this->runner = new Runner($input, $output);
    $this->runner::setState('run_suite_called', FALSE);
    $files = new LocalFilesProvider(__DIR__ . '/../TestingMixins');
    $this->runner->setFiles($files);
    $container->set('runner', $this->runner);
    parent::setUp();
  }


}
