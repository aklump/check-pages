<?php

namespace AKlump\CheckPages\Files;

use AKlump\CheckPages\Exceptions\UnresolvablePathException;
use RuntimeException;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocatorInterface;

/**
 * Safely CRUD against the file system.
 */
final class LocalFilesProvider implements FilesProviderInterface, FileLocatorInterface {

  /**
   * @var array
   */
  private $locatorPaths = [];

  /**
   * @var string
   */
  private $baseDir;

  public function __construct(string $default_resolved_dir) {
    $this->setBaseResolveDir($default_resolved_dir);
  }

  private function tryAssertPathIsDir(string $path) {
    if (!is_dir($path)) {
      throw new  PathIsNotDirectoryException($path);
    }
  }

  /**
   * @param string $path
   *
   * @return void
   *
   * @see \Symfony\Component\Config\FileLocator::isAbsolutePath
   */
  private function tryAssertPathIsAbsolutePath(string $path) {
    if (!$this->isAbsolutePath($path)) {
      throw new \InvalidArgumentException(sprintf('The path must be an absolute path; "%s" is not.', $path));
    }
  }

  private function tryAssertNoLeadingDot(string $path) {
    if ('.' === $path[0]) {
      throw new \InvalidArgumentException(sprintf('Relative paths may not begin with a dot; "%s" is invalid', $path));
    }
  }

  /**
   * Returns whether the file path is an absolute path.
   */
  private function isAbsolutePath(string $file): bool {
    if ('/' === $file[0] || '\\' === $file[0]
      || (\strlen($file) > 3 && ctype_alpha($file[0])
        && ':' === $file[1]
        && ('\\' === $file[2] || '/' === $file[2])
      )
      || NULL !== parse_url($file, \PHP_URL_SCHEME)
    ) {
      return TRUE;
    }

    return FALSE;
  }

  private function tryAssertWriteable($path) {
    //    if ($this->mode !== FilesProviderInterface::MODE_READWRITE) {
    //      throw new NotWriteableException($path);
    //    }
    if (is_dir($path) && !is_writable($path)) {
      throw new NotWriteableException($path);
    }
    elseif (($parent = dirname($path)) && is_dir($parent) && !is_writable($parent)) {
      throw new NotWriteableException($path);
    }
  }

  /**
   * @inheritDoc
   */
  public function tryCreateDir(string $directory_path): FilesProviderInterface {
    if (!$this->isAbsolutePath($directory_path) && $this->baseDir) {
      $directory_path = $this->baseDir . '/' . $directory_path;
    }
    $this->tryAssertWriteable($directory_path);

    if (!is_dir($directory_path) && !mkdir($directory_path, 0755, TRUE)) {
      throw new RuntimeException(sprintf('Directory "%s" could not be created', $directory_path));
    }

    return $this;
  }

  /**
   * @inheritDoc
   */
  public function tryEmptyDir(string $absolute_dir): FilesProviderInterface {
    $this->tryAssertWriteable($absolute_dir);
    $this->tryAssertPathIsDir($absolute_dir);

    $files = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($absolute_dir, \RecursiveDirectoryIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $info) {
      $todo = ($info->isDir() ? 'rmdir' : 'unlink');
      $todo($info->getPathname());
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function tryResolveDir(string $directory_path_to_resolve, int $options = 0): array {
    if (empty($directory_path_to_resolve) && isset($this->baseDir)) {
      return [$this->baseDir];
    }
    $this->tryAssertNoLeadingDot($directory_path_to_resolve);

    $paths = [];
    foreach ($this->locatorPaths as $locator_path) {
      if ($this->isAbsolutePath($directory_path_to_resolve)) {
        if (strpos($directory_path_to_resolve, $locator_path) !== 0) {
          continue;
        }
        $candidate = $directory_path_to_resolve;
      }
      else {
        $candidate = $locator_path . '/' . $directory_path_to_resolve;
      }
      if (is_dir($candidate)) {
        $paths[] = $candidate;
      }
    }

    if (!empty($paths)) {
      return array_unique($paths);
    }
    elseif ($options & FilesProviderInterface::RESOLVE_NON_EXISTENT_PATHS && $this->baseDir && !$this->isAbsolutePath($directory_path_to_resolve)) {
      return [$this->baseDir . '/' . $directory_path_to_resolve];
    }
    throw new UnresolvablePathException($directory_path_to_resolve);
  }

  public function addResolveDir(string $directory, string $mode = FilesProviderInterface::MODE_READONLY): FilesProviderInterface {
    if (!$this->isAbsolutePath($directory)) {
      if (empty($this->baseDir)) {
        throw new \InvalidArgumentException(sprintf('The relative directory "%s" cannot be made absolute due to missing base directory.', $directory));
      }
      $directory = $this->baseDir . '/' . $directory;
    }

    $this->tryAssertPathIsAbsolutePath($directory);
    $this->tryAssertPathIsDir($directory);

    $this->locatorPaths = $this->locatorPaths ?? [];
    if (!in_array($directory, $this->locatorPaths)) {
      $this->locatorPaths[] = $directory;
    }

    // They must be in strlen, descending order for correct resolutions.
    usort($this->locatorPaths, function ($a, $b) {
      return strlen($b) - strlen($a);
    });

    return $this;
  }

  /**
   * @inheritDoc
   */
  public function tryResolveFile(string $filepath_to_resolve, array $extensions = [], int $options = 0): array {
    $paths = [];
    if (empty($extensions)) {
      $extension = pathinfo($filepath_to_resolve, PATHINFO_EXTENSION);
      if (!$extension) {
        throw new UnresolvablePathException($filepath_to_resolve);
      }
      $extensions = [$extension];
    }
    foreach ($extensions as $extension) {
      if ($extension !== substr($filepath_to_resolve, -1 * strlen($extension))) {
        $candidate = "$filepath_to_resolve.$extension";
      }
      else {
        $candidate = $filepath_to_resolve;
      }

      foreach ($this->locatorPaths as $locator_path) {
        if ($this->isAbsolutePath($candidate)) {
          if (strpos($candidate, $locator_path) !== 0) {
            continue;
          }
          $path = $candidate;
        }
        else {
          $path = $locator_path . '/' . $candidate;
        }

        $located = [];
        if (strpos($candidate, '*')) {
          $located = glob($path);
        }
        elseif (file_exists($path)) {
          $located[] = $path;
        }
        if ($located && $options & FilesProviderInterface::RESOLVE_TO_RESOLVE_DIRS) {
          $located = [$locator_path];
        }
        $paths = array_merge($paths, $located);
      }
    }
    if (!empty($paths)) {
      return array_unique($paths);
    }
    elseif ($options & FilesProviderInterface::RESOLVE_NON_EXISTENT_PATHS && $this->baseDir && !$this->isAbsolutePath($filepath_to_resolve)) {
      return [$this->baseDir . '/' . $filepath_to_resolve];
    }
    throw new UnresolvablePathException($filepath_to_resolve);
  }

  /**
   * {@inheritdoc}
   */
  public function tryWriteFile(string $absolute_file_path, string $data): FilesProviderInterface {
    $this->tryAssertWriteable($absolute_file_path);

    if (!file_put_contents($absolute_file_path, $data)) {
      throw new NotWriteableException($absolute_file_path);
    }

    return $this;
  }

  /**
   * Returns a full path for a given file name.
   *
   * @param string $name The file name to locate
   * @param string|null $currentPath The current path
   * @param bool $first Whether to return the first occurrence or an array of filenames
   *
   * @return string|array The full path to the file or an array of file paths
   *
   * @throws \InvalidArgumentException        If $name is empty
   * @throws FileLocatorFileNotFoundException If a file is not found
   */
  public function locate(string $name, string $currentPath = NULL, bool $first = TRUE) {
    try {
      $locator = clone $this;
      if (NULL !== $currentPath) {
        $locator->addResolveDir($currentPath);
      }
      $filepaths = $locator->tryResolveFile($name);
      if ($first) {
        return $filepaths[0];
      }

      return $filepaths;
    }
    catch (\Exception $exception) {
      throw new FileLocatorFileNotFoundException(sprintf('The file "%s" does not exist.', $name), 0, NULL, [$name]);
    }
  }

  public function setBaseResolveDir(string $absolute_dir, string $mode = FilesProviderInterface::MODE_READONLY): FilesProviderInterface {
    // The base directory must be an absolute path, this is different from other
    // resolved directories, which if relative will be made absolute using the
    // base directory.
    if (!$this->isAbsolutePath($absolute_dir)) {
      throw new \InvalidArgumentException(sprintf('The base resolve directory must be an absolute path; "%s" is not.', $absolute_dir));
    }
    $this->addResolveDir($absolute_dir, $mode);
    $this->baseDir = $absolute_dir;

    return $this;
  }
}
