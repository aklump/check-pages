<?php

namespace AKlump\CheckPages\Handlers;

use AKlump\CheckPages\Event;

/**
 * Implements the Import handler.
 */
final class Import implements HandlerInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::SUITE_STARTED => [
        function (Event\SuiteEventInterface $event) {
          $suite = $event->getSuite();
          $importer = new Importer($event->getSuite()->getRunner()->getFiles());
          foreach ($suite->getTests() as $test) {
            $config = $test->getConfig();

            // Handle TEST imports.
            if (!empty($config['import'])) {
              $insert_code = $importer->loadImport($config['import']);
              $suite->replaceTestWithMultiple($test, $insert_code);
            }

            elseif (!empty($config['find']) && is_array($config['find'])) {
              $importer->resolveImports($config['find']);
              $test->setConfig($config);
            }
          }
        },
      ],
    ];
  }

  public static function getId(): string {
    return 'import';
  }

}
