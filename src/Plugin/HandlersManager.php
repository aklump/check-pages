<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Handlers\HandlerInterface;
use AKlump\CheckPages\Parts\Runner;
use AKlump\LoftLib\Code\Strings;

/**
 * Handles integration of handlers with the main app.
 */
final class HandlersManager {

  static $handlers = [];

  private $runner;

  /**
   * @var array
   */
  private $pathToHandlers;

  /**
   * HandlersManager constructor.
   *
   * @param string $path_to_handlers
   *   Path to the directory containing all handlers.
   */
  public function __construct(string $path_to_handlers) {
    $this->pathToHandlers = $path_to_handlers;
  }

  public function setRunner(Runner $runner): void {
    $this->runner = $runner;
  }

  /**
   * Return a list of all handlers.
   *
   * @return array
   *   Each element is keyed with:
   *   - id string Plugin id.
   *   - filepath string Path to the plugin directory.
   */
  public function getAllHandlers(): array {
    $base_path = $this->pathToHandlers;
    $directory_listing = scandir($base_path);
    self::$handlers = [];
    foreach ($directory_listing as $basename) {
      $filepath = "$base_path/$basename";
      if ($basename !== '.' && $basename !== '..' && is_dir($filepath)) {
        $id = pathinfo($filepath, PATHINFO_FILENAME);
        $filename = Strings::upperCamel($id);
        self::$handlers[] = [
          'id' => $id,
          'path' => $filepath,
          'autoload' => $filepath . "/$filename.php",
          'classname' => '\\AKlump\\CheckPages\\Handlers\\' . $filename,
        ];
      }
    }

    return self::$handlers;
  }

  /**
   * Return a new plugin instance by ID.
   *
   * @param string $handler_id
   *   The handler ID.
   *
   * @return \AKlump\CheckPages\Handlers\HandlerInterface
   *   A plugin instance.
   *
   * @throws \RuntimeException
   *   If the class cannot be instantiated.
   */
  public function getHandlerInstance(string $handler_id): HandlerInterface {
    $handlers = $this->getAllHandlers();
    foreach ($handlers as $handler) {
      if ($handler['id'] === $handler_id) {
        if (!class_exists($handler)) {
          require_once $handler['autoload'];
        }

        return new $handler['classname']($this->runner);
      }
    }
    throw new \RuntimeException(sprintf('Could not instantiate plugin %s', $handler_id));
  }

}
