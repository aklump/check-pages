<?php

namespace AKlump\CheckPages\Tests\Unit\Files;

use AKlump\CheckPages\CheckPages;
use AKlump\CheckPages\Files\FilesProviderInterface;
use AKlump\CheckPages\Files\LocalFilesProvider;
use AKlump\CheckPages\Files\NotInResolvableDirectoryException;
use AKlump\CheckPages\Files\NotWriteableException;
use AKlump\CheckPages\Files\PathIsNotDirectoryException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;

/**
 * @covers \AKlump\CheckPages\Files\LocalFilesProvider
 */
final class LocalFilesProviderTest extends TestCase {

  public function _testTryWriteFileWorksAsExpected() {
    $files = new LocalFilesProvider();
    $base_path = $this->establishTempDir();
    $files->tryWriteFile("$base_path/data.json", json_encode(['one', 'two']));
    $this->assertSame('["one","two"]', file_get_contents("$base_path/data.json"));
  }

  public function _testTryEmptyDirWorksAsExpected() {
    $files = new LocalFilesProvider();
    $base_path = $this->establishTempDir();


    $files->tryCreateDir("$base_path/foo/bar");
    $this->assertTrue(touch("$base_path/foo/bar/baz.txt"));

    $this->assertTrue(is_dir("$base_path/foo"));
    $this->assertTrue(is_dir("$base_path/foo/bar"));
    $this->assertTrue(file_exists("$base_path/foo/bar/baz.txt"));

    $files->tryEmptyDir("$base_path/foo");
    $this->assertTrue(is_dir("$base_path/foo"));
    $this->assertFalse(is_dir("$base_path/foo/bar"));
    $this->assertFalse(file_exists("$base_path/foo/bar/baz.txt"));
  }

  public function _testTryEmptyDirThrowsOnFileArgument() {
    $files = new LocalFilesProvider();
    $base_path = $this->establishTempDir();

    $files->tryCreateDir("$base_path/foo/bar");
    $this->assertTrue(touch("$base_path/foo/bar/baz.txt"));

    $this->expectException(PathIsNotDirectoryException::class);
    $files->tryEmptyDir("$base_path/foo/bar/baz.txt");
  }

  public function _testTryCreateDirWorksAsExpected() {
    $files = new LocalFilesProvider();
    $base_path = $this->establishTempDir();

    $result = $files->tryCreateDir("$base_path/foo");
    $this->assertSame($files, $result);
    $this->assertTrue(is_dir("$base_path/foo"));
  }

  /**
   * Provides data for testTryDestructiveMethodOutsideBaseThrows.
   */
  public function dataForTestDestructiveMethodsProvider() {
    $tests = [];
    $tests[] = ['tryCreateDir', ['$base_path/bar']];
    $tests[] = ['tryEmptyDir', ['$base_path/bar']];
    $tests[] = ['tryWriteFile', ['$base_path/bar/list.csv', 'lorem,ipsum']];

    return $tests;
  }

  /**
   * @dataProvider dataForTestDestructiveMethodsProvider
   * @testdox Calling $method outside base throws.
   *
   * @param string $method
   */
  public function _testTryDestructiveMethodOutsideBaseThrows(string $method, array $args) {
    $base_path = $this->establishTempDir('foo');

    // This is a valid location to create a directory because it is writeable,
    // but it's outside of our instance root, so for that reason you cannot
    // create a directory in there.
    $base_path = $this->establishTempDir('bar', $base_path);
    $this->assertTrue(is_writable("$base_path/bar"));

    $files = new LocalFilesProvider("$base_path/foo", FilesProviderInterface::MODE_READWRITE);
    $this->expectException(NotInResolvableDirectoryException::class);
    call_user_func_array([
      $files,
      $method,
    ], array_map(function ($arg) use ($base_path) {
      if (is_string($arg)) {
        $arg = str_replace('$base_path', $base_path, $arg);
      }

      return $arg;
    }, $args));
  }

  /**
   * @dataProvider dataForTestDestructiveMethodsProvider
   * @testdox Calling $method in read-only instance throws.
   *
   * @param string $method
   */
  public function _testTryDestructiveMethodInReadOnlyInstanceThrows(string $method, array $args) {
    $files = new LocalFilesProvider();
    $base_path = $this->establishTempDir();

    $this->expectException(NotWriteableException::class);
    call_user_func_array([
      $files,
      $method,
    ], array_map(function ($arg) use ($base_path) {
      if (is_string($arg)) {
        $arg = str_replace('$base_path', $base_path, $arg);
      }

      return $arg;
    }, $args));
  }

  public function _testTryResolveFileReturnsArrayWithFilesScenarioB() {
    $files = new LocalFilesProvider();
    $base_path = $this->establishTempDir('foo');
    $this->establishTempDir('bar', $base_path);

    $files->addResolveDir("$base_path");
    $files->addResolveDir("$base_path/bar");

    touch("$base_path/foo/a.txt");
    touch("$base_path/bar/a.txt");

    $located_paths = $files->tryResolveFile('a.txt');
    $this->assertCount(1, $located_paths);
    $this->assertContains("$base_path/bar/a.txt", $located_paths);

    $located_paths = $files->tryResolveFile('bar/a.txt');
    $this->assertCount(1, $located_paths);
    $this->assertContains("$base_path/bar/a.txt", $located_paths);

    $located_paths = $files->tryResolveFile("$base_path/bar/a.txt");
    $this->assertCount(1, $located_paths);
    $this->assertContains("$base_path/bar/a.txt", $located_paths);

    $located_paths = $files->tryResolveFile('foo/a.txt');
    $this->assertCount(1, $located_paths);
    $this->assertContains("$base_path/foo/a.txt", $located_paths);

    $located_paths = $files->tryResolveFile("$base_path/foo/a.txt");
    $this->assertCount(1, $located_paths);
    $this->assertContains("$base_path/foo/a.txt", $located_paths);
  }

  public function testLocateWorksWithCurrentPathArg() {
    $base_path = $this->establishTempDir('foo');
    $files = new LocalFilesProvider($base_path);
    touch("$base_path/foo/a.txt");

    $result = $files->locate('a.txt', "$base_path/foo");
    $this->assertSame("$base_path/foo/a.txt", $result);

    $result = $files->locate('a.txt', "$base_path/foo", FALSE);
    $this->assertSame("$base_path/foo/a.txt", $result[0]);

    $this->expectException(FileLocatorFileNotFoundException::class);
    $files->locate('a.txt');
  }

  public function testLocateCurrentArgumentDoesNotBleedIntoInstance() {
    $base_path = $this->establishTempDir('foo');
    $files = new LocalFilesProvider($base_path);
    touch("$base_path/foo/a.txt");

    $result = $files->locate('a.txt', "$base_path/foo");
    $this->assertSame("$base_path/foo/a.txt", $result);

    $this->expectException(\InvalidArgumentException::class);
    $files->tryResolveFile('a.txt');
  }

  public function testLocateDoesNotResolveRelativePathsAndThrowsPerSymfonyInterface() {
    $base_path = $this->establishTempDir();
    $files = new LocalFilesProvider($base_path);
    $this->expectException(FileLocatorFileNotFoundException::class);
    $files->locate('a.txt');
  }

  public function testLocateThrowsAsExpected() {
    $base_path = $this->establishTempDir('foo');
    $files = new LocalFilesProvider($base_path);
    touch("$base_path/foo/a.txt");

    $this->expectException(FileLocatorFileNotFoundException::class);
    $files->locate('a.txt');
  }

  public function _testTryResolveFileThrowsWhenExtensionIsOutsideScope() {
    $files = new LocalFilesProvider();
    $base_dir = $this->establishTempDir();
    $files->addResolveDir($base_dir);
    touch("$base_dir/foo.txt");
    $this->assertCount(1, $files->tryResolveFile('foo', ['txt']));
    $this->expectException(\InvalidArgumentException::class);
    $files->tryResolveFile('foo', ['yml', 'yaml']);
  }

  public function testTryResolveFileReturnsArrayWithFilesScenarioA() {
    $base_path = $this->establishTempDir('foo');
    $files = new LocalFilesProvider($base_path);
    $this->establishTempDir('bar', $base_path);

    $files->addResolveDir("foo");
    $files->addResolveDir("bar");

    touch("$base_path/foo/a.txt");
    touch("$base_path/bar/a.txt");

    $located_paths = $files->tryResolveFile('a.txt');
    $this->assertCount(2, $located_paths);
    $this->assertContains("$base_path/foo/a.txt", $located_paths);
    $this->assertContains("$base_path/bar/a.txt", $located_paths);

    $located_paths = $files->tryResolveFile('foo/a.txt');
    $this->assertCount(1, $located_paths);
    $this->assertContains("$base_path/foo/a.txt", $located_paths);

    $located_paths = $files->tryResolveFile('bar/a.txt');
    $this->assertCount(1, $located_paths);
    $this->assertContains("$base_path/bar/a.txt", $located_paths);
  }

  public function testTryResolveFileResolvesToBaseDirWhenNotExistsWithOption() {
    $base_path = $this->establishTempDir();
    $files = new LocalFilesProvider($base_path);
    $files->addResolveDir($base_path);
    $resolved = $files->tryResolveFile('lorem.csv', [], FilesProviderInterface::RESOLVE_NON_EXISTENT_PATHS)[0];
    $this->assertSame("$base_path/lorem.csv", $resolved);
  }

  public function testTryResolveDirFindsDirScenarioB() {
    $base_dir = $this->establishTempDir('example/tests');
    $this->establishTempDir(CheckPages::DIR_HANDLERS, $base_dir);

    $files = new LocalFilesProvider($base_dir);
    $files->addResolveDir("$base_dir/example/tests");

    $dir = $files->tryResolveDir(CheckPages::DIR_HANDLERS)[0];
    $this->assertSame("$base_dir/" . CheckPages::DIR_HANDLERS, $dir);
  }

  public function testTryResolveDirFindsDir() {
    $base_path = $this->establishTempDir();
    $files = new LocalFilesProvider($base_path);
    $this->assertTrue(mkdir($base_path . '/foo/bar', 0755, TRUE));

    $dir = $files->tryResolveDir('foo/bar')[0];
    $this->assertSame("$base_path/foo/bar", $dir);

    $dir = $files->tryResolveDir("$base_path/foo/bar")[0];
    $this->assertSame("$base_path/foo/bar", $dir);
  }

  public function testTryResolveDirDoesNotResolveToExistingCWDDirectory() {

    // Create a relative directory in the CWD to act as a potential decoy.
    $cwd_test_dir = basename($this->establishTempDir());
    $this->assertTrue(mkdir($cwd_test_dir));

    $base_dir = $this->establishTempDir();
    $files = new LocalFilesProvider($base_dir);
    try {

      // Resolve to a non-existent path within our base dir.
      $resolved = $files->tryResolveDir($cwd_test_dir, FilesProviderInterface::RESOLVE_NON_EXISTENT_PATHS)[0];

      // ... instead we should get a path base on the base directory.
      $this->assertSame("$base_dir/$cwd_test_dir", $resolved);
    }
    catch (\Exception $exception) {
      throw $exception;
    }
    finally {
      $this->assertTrue(rmdir($cwd_test_dir));
    }
  }

  public function testAddResolveDirThrowsWhenArgumentIsAnExistingFile() {
    $base_dir = $this->establishTempDir();
    $files = new LocalFilesProvider($base_dir);
    touch("$base_dir/foo");

    $this->expectException(\InvalidArgumentException::class);
    $files->addResolveDir("$base_dir/foo");
  }

  //
  // Directories
  //
  public function testTryResolveDirThrowsWithNonExistentDotPrefixedRelativePath() {
    $base_path = $this->establishTempDir();
    $files = new LocalFilesProvider($base_path);
    $this->expectException(\InvalidArgumentException::class);
    $files->tryResolveDir('./foo/bar');
  }

  public function testTryResolveDirThrowsWithNonExistentAbsolutePath() {
    $base_path = $this->establishTempDir();
    $files = new LocalFilesProvider($base_path);
    $this->expectException(\InvalidArgumentException::class);
    $files->tryResolveDir('/foo/bar');
  }

  public function testTryResolveDirReturnsNonExistentDirectoryPathBasedOnBaseDirectoryWithRelativePath() {
    $base_path = $this->establishTempDir();
    $files = new LocalFilesProvider($base_path);
    $resolved = $files->tryResolveDir('foo/bar', FilesProviderInterface::RESOLVE_NON_EXISTENT_PATHS)[0];
    $this->assertSame("$base_path/foo/bar", $resolved);
  }

  public function testTryResolveDirThrowsOnNonExistentDirectoryWhenNotPassingOption() {
    $base_path = $this->establishTempDir();
    $files = new LocalFilesProvider($base_path);
    $this->expectException(\InvalidArgumentException::class);
    $files->tryResolveDir('foo/bar');
  }

  public function testTryResolveDirWithEmptyStringReturnsBaseDirectory() {
    $base_path = $this->establishTempDir();
    $files = new LocalFilesProvider($base_path);
    $this->assertSame($base_path, $files->tryResolveDir('')[0]);
  }

  //
  // Constructor
  //
  public function testConstructorSetsBaseDirectoryAsExpected() {
    $base_path = $this->establishTempDir();
    $files = new LocalFilesProvider($base_path);
    $this->assertInstanceOf(FilesProviderInterface::class, $files);
  }

  public function testConstructorThrowsWhenDirDoesntExist() {
    $this->expectException(\InvalidArgumentException::class);
    $base_path = $this->establishTempDir();
    new LocalFilesProvider("$base_path/bogus");
  }

  public function testConstructorThrowsWhenDirExistsButIsPassedAsRelativePath() {
    $relative_dir = microtime(TRUE);
    $this->assertTrue(mkdir($relative_dir));
    try {
      $this->expectException(\InvalidArgumentException::class);
      new LocalFilesProvider($relative_dir);
    }
    catch (\Exception $exception) {
      throw $exception;
    }
    finally {
      $this->assertTrue(rmdir($relative_dir));
    }
  }

  /**
   * @param string $relative
   * @param string|null $base
   *
   * @return string
   *   The base path in which $relative was created in the temp dir.
   */
  private function establishTempDir(string $relative = '', string $base_path = NULL): string {
    $base_path = $base_path ?? sys_get_temp_dir() . '/' . microtime(TRUE);
    $absolute = "$base_path/$relative";
    $this->assertFalse(file_exists($absolute));
    $this->assertTrue(mkdir($absolute, 0755, TRUE));

    return $base_path;
  }

}
