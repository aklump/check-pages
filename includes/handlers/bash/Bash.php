<?php

namespace AKlump\CheckPages\Handlers;

use AKlump\CheckPages\Assert;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Output\Message;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\Messaging\MessageType;

/**
 * Implements the Bash handler.
 */
final class Bash implements HandlerInterface {

  use \AKlump\CheckPages\Traits\SetTrait;

  /**
   * {@inheritdoc}
   */
  public static function getId(): string {
    return 'bash';
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::TEST_CREATED => [
        function (TestEventInterface $event) {
          $test = $event->getTest();
          if (!$test->has('bash')) {
            return;
          }

          $bash_command = $test->get('bash');
          $test->getSuite()->interpolate($bash_command);

          $bash_output = [];
          $test_result = 0;
          $set_value = exec($bash_command, $bash_output, $test_result);
          $set_key = $test->get('set');

          $test_result == 0 ? $test->setPassed() : $test->setFailed();

          $message_type = MessageType::DEBUG;
          $verbosity = Verbosity::VERBOSE;
          if ($test->hasFailed()) {
            $message_type = MessageType::ERROR;
            $verbosity = $verbosity | Verbosity::DEBUG;
          }
          $test->addMessage(new Message(
            $bash_output,
            $message_type,
            $verbosity
          ));

          if ($set_key) {
            $config = $test->getConfig();
            $config['value'] = $set_value;
            $test->setConfig($config);
            $handler = new self();
            $lines[] = $handler->setKeyValuePair(
              $test->getSuite()->variables(),
              $config[Assert::ASSERT_SETTER],
              $config['value']
            );
            $test->addMessage(new Message($lines, MessageType::DEBUG, Verbosity::DEBUG));
          }
        },
      ],
      Event::TEST_FINISHED => [
        function (TestEventInterface $event) {
          $test = $event->getTest();
          if ($test->has(Assert::ASSERT_SETTER)) {
            $suite = $test->getSuite();
            $config = $test->getConfig();
            $suite->interpolate($config['value']);
            $suite->interpolate($config[Assert::ASSERT_SETTER]);
          }
        },
      ],
    ];
  }

}
