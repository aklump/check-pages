<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Parts\SetTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Implements the Import plugin.
 */
final class Import implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::SUITE_LOADED => [
        function (Event\SuiteEventInterface $event) {
          $suite = $event->getSuite();
          $importer = new Importer($event->getSuite()->getRunner());
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

}
