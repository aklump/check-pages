<?php

namespace AKlump\CheckPages;

use AKlump\CheckPages\Exceptions\UnresolvablePathException;
use AKlump\CheckPages\Parts\Runner;

class Files {

  /**
   * @var \AKlump\CheckPages\Parts\Runner
   */
  private $runner;

  public function __construct(Runner $runner) {
    $this->runner = $runner;
  }

  /**
   * Resolves a directory so it exists and is writeable.
   *
   * @param string $directory_path
   *   An absolute or resolvable directory path.  It does not have to exist, but
   *   at least some parent does.
   *
   * @return string
   *   The resolved path to the directory which is writeable and exists.
   *
   * @throws \AKlump\CheckPages\Exceptions\UnresolvablePathException
   *   If $path cannot be resolved, created or made writeable.  Or if $path
   *   appears to be a file.
   */
  public function prepareDirectory(string $directory_path): string {
    try {
      $path = $directory_path;
      if (pathinfo($path, PATHINFO_EXTENSION)) {
        throw new UnresolvablePathException('');
      }
      $suffix = [];
      while ($path && !$this->isResolved($path)) {
        preg_match("/(.+)\/(.+?)$/", $path, $matches);
        $path = $matches[1];
        $suffix[] = $matches[2];
      }

      $output_dir = rtrim($path, '/') . '/' . implode('/', $suffix);
      if (file_exists($output_dir) && !is_dir($output_dir)) {
        throw new UnresolvablePathException('');
      }
    }
    catch (UnresolvablePathException $exception) {
      throw new UnresolvablePathException(sprintf('Only directories may be passed to this method: %s looks like a file.', $path));
    }

    if (!is_writable($output_dir) && !mkdir($output_dir, 0777, TRUE)) {
      throw new UnresolvablePathException(sprintf('The output directory "%s" is not writeable and cannot be created.  Please update it\'s permissions.', $output_dir));
    }

    return $output_dir;
  }

  private function isResolved(string &$path): bool {

    // Absolute paths are resolved by definition.
    if (substr($path, 0, 1) === '/') {
      return TRUE;
    }
    try {
      $resolved = NULL;
      $this->runner->resolve($path, $resolved);

      $path = $resolved . '/' . $path;

      return TRUE;
    }
    catch (UnresolvablePathException $exception) {
      return FALSE;
    }
  }
}
