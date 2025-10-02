<?php

namespace AKlump\CheckPages\Files;

/**
 * Resolves relative file paths recursively within an array structure.
 *
 * This class is designed to process a given array, identify relative file
 * paths, and resolve them to their absolute paths based on a specified base
 * path IF THEY EXIST.
 */
class ResolveRecursive {

  /**
   * @param array $data Any value that is a relative path to $base_path, exists,
   * and begins with `./` or `../` will be replaced with the absolute file path.
   * @param string $base_path
   *
   * @return array A new array with possible resolutions.
   */
  public function __invoke(array $data, string $base_path): array {
    $result = $data;
    $this->resolveRelativePaths($result, $base_path);

    return $result;
  }

  private function resolveRelativePaths(&$value, string $base_path) {
    if (!is_array($value)) {
      if (is_string($value) && (str_starts_with($value, './') || str_starts_with($value, '../'))) {
        $test_path = $base_path . '/' . $value;
        if (file_exists($test_path)) {
          $value = realpath($test_path);
        }
      }

      return;
    }
    foreach ($value as &$v) {
      $this->resolveRelativePaths($v, $base_path);
    }
  }
}
