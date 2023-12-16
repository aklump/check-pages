<?php

namespace AKlump\CheckPages\Handlers;

use AKlump\CheckPages\Assert;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Output\DebugMessage;
use AKlump\CheckPages\Output\Message;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\CheckPages\Parts\Test;
use AKlump\Messaging\MessageType;

abstract class CommandLineTestHandlerBase implements HandlerInterface {

  abstract protected function prepareCommandForCLI(string $command): string;

  private function appliesToTest(Test $test): bool {
    return $test->has($this->getId());
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::TEST_CREATED => [
        function (TestEventInterface $event) {
          $test = $event->getTest();
          $handler = new static();
          if (!$handler->appliesToTest($test)) {
            return;
          }

          $config_key = $handler->getId();
          $command = $test->get($config_key);
          $test->getSuite()->interpolate($command);
          $prepared_command = $handler->prepareCommandForCLI($command);

          $command_output = [];
          $test_result = 0;
          exec($prepared_command, $command_output, $test_result);
          $test_result == 0 ? $test->setPassed() : $test->setFailed();

          if ($test->get(Assert::ASSERT_SETTER)) {
            $set_value = trim(implode(PHP_EOL, $command_output));
            if ('' === $set_value) {
              $test->addMessage(new DebugMessage(
                [sprintf('%s has no output.', $prepared_command)],
              ));
            }
            $test->set('value', $set_value);
          }

          $message_type = MessageType::DEBUG;
          $verbosity = Verbosity::VERBOSE;
          if ($test->hasFailed()) {
            $message_type = MessageType::ERROR;
            $verbosity = $verbosity | Verbosity::DEBUG;
          }
          $test->addMessage(new Message(
            $command_output,
            $message_type,
            $verbosity
          ));
        },
      ],
    ];
  }

}
