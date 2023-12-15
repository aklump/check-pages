<?php

namespace AKlump\CheckPages\Helpers;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use AKlump\CheckPages\Files\LocalFilesProvider;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use AKlump\CheckPages\CheckPages;
use AKlump\CheckPages\Plugin\HandlersManager;

class BuildContainer {

  /**
   * @var \AKlump\CheckPages\Files\LocalFilesProvider
   */
  private LocalFilesProvider $files;

  public function __construct(LocalFilesProvider $files_provider) {
    $this->files = $files_provider;
  }

  public function __invoke(): Container {
    $container = new ContainerBuilder();
    $loader = new YamlFileLoader($container, $this->files);
    $loader->load('services.DO_NOT_EDIT.yml');

    $this->addHandlersManagerService($container);

    return $container;
  }

  private function addHandlersManagerService(Container $container) {
    $path_to_handlers = realpath($this->files->tryResolveDir('includes/' . CheckPages::DIR_HANDLERS)[0]);
    $handlers_manager = new HandlersManager($path_to_handlers);
    $container->set('handlers_manager', $handlers_manager);
  }
}
