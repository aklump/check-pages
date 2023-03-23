<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Assert;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Output\Message;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\Traits\SetTrait;
use AKlump\Messaging\MessageType;

/**
 * Implements the Value plugin.
 */
final class Value implements PluginInterface {

  use SetTrait;

  public static function doesApply($context): bool {
    if ($context instanceof Assert || $context instanceof Test) {
      return array_key_exists('value', $context->getConfig());
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [

      //
      // Handle setting/asserting from test-level.
      //
      Event::TEST_STARTED => [
        function (Event\DriverEventInterface $event) {
          $test = $event->getTest();
          if (!self::doesApply($test)) {
            return;
          }

          $config = $test->getConfig();
          $test->interpolate($config['value']);

          // Handle a test-level setter.
          $set_message = '';
          if (array_key_exists('set', $config)) {
            $test->interpolate($config['set']);
            $obj = new self();
            $set_message = $obj->setKeyValuePair(
              $test->getSuite()->variables(),
              $config['set'],
              $config['value']
            );
            $title = $config['why'] ?? $set_message;
            $test->addMessage(new Message([$title],
              MessageType::DEBUG,
              Verbosity::VERBOSE
            ));
            $test->setPassed();
          }

          // Handle a test-level assertion.
          $test_level_assertion = array_intersect_key([
            'is' => Assert::ASSERT_EQUALS,
            'is not' => Assert::ASSERT_NOT_EQUALS,
            'contains' => Assert::ASSERT_CONTAINS,
            'not contains' => Assert::ASSERT_NOT_CONTAINS,
            'matches' => Assert::ASSERT_MATCHES,
            'not matches' => Assert::ASSERT_NOT_MATCHES,
          ], $config);
          if ($test_level_assertion) {
            $type = key($test_level_assertion);
            $definition = [
              $type => $config['value'],
            ];
            $assert = new Assert('value', $definition, $test);
            $assert->setHaystack([$config['value']]);
            $assert->setAssertion($test_level_assertion[$type], $config[$type]);
            $assert->run();

            $test->addMessage(new Message([
              ltrim($set_message . '. ', '. ') . $assert,
            ],
              MessageType::INFO,
              Verbosity::VERBOSE
            ));

            if ($assert->hasPassed()) {
              $test->setPassed();
            }
            elseif ($assert->hasFailed()) {
              $test->setFailed();
            }
          }
        },
        -10,
      ],
      Event::ASSERT_CREATED => [
        function (Event\AssertEventInterface $event) {
          $assert = $event->getAssert();
          if (!self::doesApply($assert)) {
            return;
          }
          $event->getAssert()->setHaystack([$assert->value]);
        },
        -10,
      ],
    ];
  }

  public static function getPluginId(): string {
    return 'value';
  }

}
