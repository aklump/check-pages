<?php

namespace AKlump\CheckPages\Tests\TestingTraits;

use AKlump\CheckPages\Files\LocalFilesProvider;
use AKlump\CheckPages\Handlers\AddHandlerAutoloads;
use AKlump\CheckPages\Helpers\BuildContainer;
use AKlump\CheckPages\Parts\Runner;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Trait TestWithDispatcherTrait
 *
 * This trait provides a method to retrieve a Runner object with a set dispatcher
 * and all expected event handlers.
 */
trait TestWithDispatcherTrait {

  /**
   * @return \AKlump\CheckPages\Parts\Runner
   *   A runner whose dispatcher is set with all expected event handlers.
   */
  public function getRunner(): Runner {
    $root_files = new LocalFilesProvider(ROOT);

    global $container;
    $container = (new BuildContainer($root_files))();

    $handlers_manager = $container->get('handlers_manager');
    $loader = require 'vendor/autoload.php';
    (new AddHandlerAutoloads($handlers_manager))($loader);

    $input = $this->createConfiguredMock(InputInterface::class, [
      'getOptions' => [
        'debug' => FALSE,
        'verbose' => FALSE,
        'show' => '',
      ],
    ]);
    $output = $this->getMockBuilder(OutputInterface::class)
      ->getMock();
    $runner = new Runner($input, $output);

    return $runner;
  }

}
