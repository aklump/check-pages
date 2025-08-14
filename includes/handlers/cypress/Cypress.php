<?php

namespace AKlump\CheckPages\Handlers;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Output\Icons;
use AKlump\CheckPages\Output\Message\DebugMessage;
use AKlump\CheckPages\Output\Message\Message;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\Messaging\MessageType;

/**
 * Implements the Cypress handler.
 */
final class Cypress implements HandlerInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::REQUEST_STARTED => [
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

          if (empty($cypress_config)) {
            $test->addMessage(new Message([
              'The test could not be run due to missing runner configuration: extras.cypress.cypress',
            ], MessageType::ERROR));
            $test->setFailed();

            return;
          }

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
            $command[] = sprintf("--env '%s'", $env);
          }

          $test->addMessage(new Message([Icons::FOOTBALL . "Handoff to Cypress..."], MessageType::INFO, Verbosity::VERBOSE));

          $command = implode(' ', $command);
          $test->getSuite()->interpolate($command);
          $test->addMessage(new DebugMessage([$command]));
          $test->echoMessages();

          $cypress_output = [];
          $test_result = 0;
          exec($command, $cypress_output, $test_result);

          $test_result == 0 ? $test->setPassed() : $test->setFailed();

          $message_type = MessageType::DEBUG;
          $verbosity = Verbosity::VERBOSE;
          if ($test->hasFailed()) {
            $message_type = MessageType::ERROR;
            $verbosity = $verbosity | Verbosity::DEBUG;
          }
          $test->addMessage(new Message(
            $cypress_output,
            $message_type,
            $verbosity
          ));

        },
      ],
    ];
  }

  public static function getId(): string {
    return 'cypress';
  }

}
