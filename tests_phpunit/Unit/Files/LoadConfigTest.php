<?php

namespace AKlump\CheckPages\Tests\Unit\Files;

use AKlump\CheckPages\Exceptions\UnresolvablePathException;
use AKlump\CheckPages\Files\LoadConfig;
use AKlump\CheckPages\Files\LocalFilesProvider;
use AKlump\CheckPages\Tests\TestingTraits\TestWithFilesTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\Files\LoadConfig
 * @uses   \AKlump\CheckPages\Files\LocalFilesProvider
 * @uses   \AKlump\CheckPages\Files\ResolveRecursive
 * @uses   \AKlump\CheckPages\Exceptions\StopRunnerException
 * @uses   \AKlump\CheckPages\Exceptions\UnresolvablePathException
 */
class LoadConfigTest extends TestCase {

  use TestWithFilesTrait;

  public function testRelativeFileGetsResolved() {
    $config_path = $this->getTestFileFilepath('app/config/dev.json', TRUE);
    file_put_contents($config_path, '{"extras":{"exists":"../alpha.yml","not_exists":"../bravo.yml"}}');
    $expected_resolved_path = $this->getTestFileFilepath('app/alpha.yml', TRUE);
    $files = new LocalFilesProvider($this->getTestFilesDirectory() . 'app/');
    $config_path = 'config/dev.json';
    $config = (new LoadConfig($files))($config_path);
    $this->assertSame($expected_resolved_path, $config['extras']['exists']);
    $this->assertSame('../bravo.yml', $config['extras']['not_exists']);
    $this->deleteTestFile('app/');
  }

  public static function dataFortestCannotParseContentsThrowsProvider(): array {
    $tests = [];
    $tests[] = [
      'lorem.json',
      '{foo"bar...$$$',
    ];
    $tests[] = [
      'lorem.json',
      'foo',
    ];
    $tests[] = [
      'lorem.yml',
      '{foo"bar...$$$',
    ];
    $tests[] = [
      'lorem.yml',
      'foo',
    ];
    $tests[] = [
      'lorem.yml',
      '[]',
    ];
    $tests[] = [
      'lorem.json',
      '[]',
    ];

    return $tests;
  }

  /**
   * @dataProvider dataFortestCannotParseContentsThrowsProvider
   */
  public function testCannotParseContentsThrows(string $basename, string $contents) {
    $expected_config_path = $this->getTestFileFilepath('config/' . $basename, TRUE);
    file_put_contents($expected_config_path, $contents);
    $files = new LocalFilesProvider($this->getTestFilesDirectory() . 'config/');
    $config_path = pathinfo($basename, PATHINFO_FILENAME);
    $this->expectException(\UnexpectedValueException::class);
    $this->expectExceptionMessageMatches('#Failed to load configuration.+due to a parse error.#');
    (new LoadConfig($files))($config_path);
    $this->deleteTestFile('config/');
  }

  public function testMissingFileThrows() {
    $files = new LocalFilesProvider($this->getTestFilesDirectory() . 'config/');
    $config_path = 'bogus';
    $this->expectException(UnresolvablePathException::class);
    $this->expectExceptionMessage('This path cannot be resolved: "bogus"');
    (new LoadConfig($files))($config_path);
  }

  public static function dataFortestCanLoadYmlFileProvider(): array {
    $tests = [];
    $tests[] = [
      'lorem.yml',
      'foo: bar',
    ];
    $tests[] = [
      'lorem.yaml',
      'foo: bar',
    ];
    $tests[] = [
      'lorem.json',
      '{"foo": "bar"}',
    ];

    return $tests;
  }

  /**
   * @dataProvider dataFortestCanLoadYmlFileProvider
   */
  public function testCanLoadAllFileTypes(string $basename, string $contents) {
    $expected_config_path = $this->getTestFileFilepath('config/' . $basename, TRUE);
    file_put_contents($expected_config_path, $contents);
    $files = new LocalFilesProvider($this->getTestFilesDirectory() . 'config/');
    $config_path = pathinfo($basename, PATHINFO_FILENAME);
    $config = (new LoadConfig($files))($config_path);
    $this->assertSame('bar', $config['foo']);
    $this->assertSame($config_path, $expected_config_path);
    $this->deleteTestFile('config/');
  }

  public function testExtensionsConstant() {
    $this->assertIsArray(LoadConfig::CONFIG_EXTENSIONS);
    $this->assertContains('yml', LoadConfig::CONFIG_EXTENSIONS);
    $this->assertContains('yaml', LoadConfig::CONFIG_EXTENSIONS);
    $this->assertContains('json', LoadConfig::CONFIG_EXTENSIONS);
  }
}
