<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Output\Feedback;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Implements the Cypress plugin.
 */
final class Cypress implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::TEST_CREATED => [
        function (TestEventInterface $event) {
          $test = $event->getTest();
          $config = $test->getConfig();
          $should_apply = array_key_exists('cypress', $config);
          if (!$should_apply) {
            return;
          }

          $event->getTest()->interpolate($config);

          $cypress_config = $test->getRunner()
                              ->getConfig()['extras']['cypress'] ?? [];
          // TODO Throw on missing or bad config. or maybe use test validate event actually.

          $command = [];
          $command[] = $cypress_config['cypress'];
          $command[] = 'run';

          $command[] = sprintf('--config-file "%s"', $cypress_config['config_file']);
          $command[] = sprintf('--spec "%s"', rtrim($cypress_config['spec_base'], '/') . '/' . ltrim($config['cypress'], '/'));

          $env = '';
          if (!empty($config['env'])) {
            foreach ($config['env'] as $name => $value) {
              if (!is_scalar($value)) {
                $value = json_encode($value);
              }
              $env .= ($env ? ',' : '') . "$name=$value";
            }
            $command[] = sprintf('--env "%s"', $env);
          }

          Feedback::updateTestStatus($test->getRunner(), "Passing to Cypress...");

          $command = implode(' ', $command);
          $cypress_output = [];
          $test_result = 0;
          exec($command, $cypress_output, $test_result);

          // TODO Fix the feedback, which does not work yet.

          $test_result == 0 ? $test->setPassed() : $test->setFailed();
          $results = [
            [
              'level' => $test->hasPassed() ? 'info' : 'error',
              'data' => implode(PHP_EOL, $cypress_output),
            ],
          ];
          $test->setResults($results);
          if ($test->hasFailed()) {
            // TODO Use correct api methods.
            echo implode(PHP_EOL, $cypress_output);
          }
        },
      ],
    ];
  }

}
