<?php

namespace AKlump\CheckPages\Files;

/**
 * Resolution Rules:
 * - Resolutions will return paths to non-existent resources if: 1) the base
 * resolve directory has been set and 2) the path being resolved is relative and
 * 3) the path cannot be resolved to at least one existing resource.
 * - Resolutions will NOT create directories or files.
 *
 */
interface FilesProviderInterface {

  /**
   * When resolving paths, if the path is relative and does not exist, return a
   * path based on the base directory.  Without this option, relative paths will
   * throw exceptions when they do not exist.
   */
  const RESOLVE_NON_EXISTENT_PATHS = 1;

  /**
   * The resolved value is the "resolve directory" that was used.  Note this is
   * not the same as dirname() of the path, it is whatever directory was used to
   * resolve the path or directory passed to the method.  This comes into play
   * when you have multiple resolve directories in an instance.
   */
  const RESOLVE_TO_RESOLVE_DIRS = 2;

  const MODE_READONLY = 'r';

  const MODE_READWRITE = 'r+';

  /**
   * @param string $absolute_directory_path
   *   An absolute path to use for resolving.
   *
   * @return \AKlump\CheckPages\Files\FilesProviderInterface
   * @throws \AKlump\CheckPages\Files\NotInResolvableDirectoryException;
   */
  public function addResolveDir(string $absolute_directory_path): FilesProviderInterface;

  /**
   * This will be used for making relative paths absolute.
   *
   * @param string $absolute_directory_path
   *
   * @return \AKlump\CheckPages\Files\FilesProviderInterface
   */
  public function setBaseResolveDir(string $absolute_directory_path): FilesProviderInterface;

  /**
   * Try to create a non-existent directory.
   *
   * @param string $absolute_directory_path
   *   The absolute path to a directory.  If it already exists nothing happens.  If it does not then directories will be recursively created.
   *
   * @return \AKlump\CheckPages\Files\FilesProviderInterface
   *   The absolute path of the successfully created or already-existing directory.
   *
   * @throws \RuntimeException
   *   If the directory cannot be created.
   * @throws \AKlump\CheckPages\Files\NotInResolvableDirectoryException
   * @throws \AKlump\CheckPages\Files\NotWriteableException
   */
  public function tryCreateDir(string $directory_path): FilesProviderInterface;

  /**
   * Write $data to a file located inside of this base path.
   *
   * @param string $absolute_file_path
   * @param string $data
   *
   * @return \AKlump\CheckPages\Files\FilesProviderInterface
   *   Self for chaining.
   *
   * @throws \AKlump\CheckPages\Files\NotInResolvableDirectoryException
   *   When trying to write to a location outside of the base dir.
   * @throws \AKlump\CheckPages\Files\NotWriteableException
   *   When trying to write to a read-only instance.
   * @see \fopen()
   *
   */
  public function tryWriteFile(string $absolute_file_path, string $data): FilesProviderInterface;

  /**
   * Try to remove all contents of a directory making it empty.
   *
   * @param string $absolute_directory_path
   *
   * @return \AKlump\CheckPages\Files\FilesProviderInterface
   *
   * @throws \InvalidArgumentException If $absolute_directory_path does not exist.
   * @throws \AKlump\CheckPages\Files\NotInResolvableDirectoryException
   * @throws \AKlump\CheckPages\Files\NotWriteableException
   */
  public function tryEmptyDir(string $absolute_directory_path): FilesProviderInterface;

  /**
   * Resolves a subpath to a directory to it's absolute.
   *
   * @param string $directory_path_to_resolve
   *
   * @return array
   *   The absolute path to the resolved directory(ies).
   */
  public function tryResolveDir(string $directory_path_to_resolve, int $options = 0): array;

  /**
   * Given a partial filepath try to resolve it to an absolute path.
   *
   * @param string $filepath_to_resolve
   *   The path, filename or basename to resolve.  This may contain globbing. If
   *   passing a basename that includes the extension and the extension does NOT
   *   match one in $extensions the basename's "extension" will be ignored, and
   *   you probably won't get what you're expecting. So either make sure the
   *   extension, if passed, is containined in $extensions, or pass the filename
   *   without extension.  If passing a relative directory and it doesn't exist
   *   an non-existent path made absolute using the base directory will be
   *   returned unless you do not pass the RESOLVE_NON_EXISTENT_PATHS option.
   * @param array $extensions
   *   One or more extensions to determine the files that will be considered.
   * @param int $options
   *   A bitmap of options to control the return value(s).
   *
   * @return array
   *   An array of absolute paths to the resolved file(s).
   *
   * @throws \AKlump\CheckPages\Exceptions\UnresolvablePathException If less than or more than one files is resolved.
   * @see \glob()
   * @see \AKlump\CheckPages\Files\FilesProviderInterface::RESOLVE_TO_RESOLVE_DIRS
   * @see \AKlump\CheckPages\Files\FilesProviderInterface::RESOLVE_NON_EXISTENT_PATHS
   */
  public function tryResolveFile(string $filepath_to_resolve, array $extensions = [], int $options = 0): array;


}
