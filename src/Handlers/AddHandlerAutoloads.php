<?php

namespace AKlump\CheckPages\Handlers;

use AKlump\CheckPages\Plugin\HandlersManager;
use AKlump\LoftLib\Code\Strings;
use Composer\Autoload\ClassLoader;

final class AddHandlerAutoloads {

  private $manager;

  public function __construct(HandlersManager $manager) {
    $this->manager = $manager;
  }

  public function __invoke(ClassLoader $class_loader) {
    $handlers = $this->manager->getAllHandlers();
    foreach ($handlers as $handler) {
      if (!empty($handler['autoload'])) {
        $class_loader->addPsr4('AKlump\CheckPages\Handlers\\', $handler['path']);
      }
      if (is_dir($handler['path'] . '/src/')) {
        $psr_prefix = 'AKlump\CheckPages\Handlers\\' . Strings::upperCamel($handler['id']) . '\\';
        $class_loader->addPsr4($psr_prefix, $handler['path'] . '/src');
      }
    }
  }

}
