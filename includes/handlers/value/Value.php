<?php

namespace AKlump\CheckPages\Handlers;

use AKlump\CheckPages\Assert;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Interfaces\HasConfigInterface;
use AKlump\CheckPages\Interfaces\MayBeInterpolatedInterface;
use AKlump\CheckPages\Output\Message;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\Traits\SetTrait;
use AKlump\Messaging\MessageType;

/**
 * Implements the Value handler.
 */
final class Value implements HandlerInterface, MayBeInterpolatedInterface {

  use SetTrait;

  public function getInterpolationKeys(int $scope): array {
    return [Assert::ASSERT_SETTER, 'value'];
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [

      Event::TEST_CREATED => [
        function (TestEventInterface $event) {
          $test = $event->getTest();
          if (!$test->has('value')) {
            return;
          }
          $handler = new self();
          if ($test->has(Assert::ASSERT_SETTER)) {
            $handler->handleSetAndValueAtTestScope($test);
          }

          // Mark test as passed if it's only going to set a value.  Setting of
          // the value to the variables will happen in the TEST_FINISHED event.
          if ($handler->isOnlySet($test)) {
            $test->setPassed();

            return;
          }

          // Otherwise create a runtime assert, run it and mark the test with
          // the result of the run.
          $assert = $handler->createAssertFromTest($test);
          $assert->run();
          $test->addMessage(new Message([strval($assert)],
            MessageType::INFO,
            Verbosity::VERBOSE
          ));

          if ($assert->hasPassed()) {
            $test->setPassed();
          }
          elseif ($assert->hasFailed()) {
            $test->setFailed();
          }
        },
        -10,
      ],
      Event::TEST_FINISHED => [
        function (TestEventInterface $event) {
          $test = $event->getTest();
          if ($test->has(Assert::ASSERT_SETTER)) {
            (new self())->handleInterpolation($test);
          }
        },
      ],
      Event::ASSERT_CREATED => [
        function (Event\AssertEventInterface $event) {
          $assert = $event->getAssert();

          $handler = new self();
          if ($handler->isOnlySet($assert)) {
            // Because this only a set event, we will mark this passed so the
            // assert doesn't run.  However we'll wait to set the value until
            // the closing event, because if this is NOT only a set event then
            // the value may need to be interpolated based on other things.
            $assert->setPassed();
            $assert->setNeedle($assert->get('value'));

            return;
          }

          if (!$assert->has('value')) {
            return;
          }

          // See if we're supposed to do a match by removing set and value to
          // see if there is still something there.  This is a more liberal than
          // looking for specific keys and we rely on the schema to validate
          // before this point.
          $test = $assert->getTest();
          $suite = $test->getSuite();
          $config = $assert->getConfig();
          unset($config[Assert::ASSERT_SETTER]);
          unset($config['value']);
          if (!empty($config)) {
            $value = $assert->get('value');
            $suite->interpolate($value);
            $assert->setHaystack([$value]);
          }
        },
        -10,
      ],

      Event:: ASSERT_FINISHED => [
        function (Event\AssertEventInterface $event) {
          $assert = $event->getAssert();
          if (!$assert->has(Assert::ASSERT_SETTER)) {
            return;
          }

          //
          // If "set" is present then we will take the value of the needle and
          // set it to the name identified by "set".
          //
          $test = $event->getTest();
          $data = [
            'name' => $assert->get(Assert::ASSERT_SETTER),
            'value' => $assert->getNeedle(),
          ];
          $test->interpolate($data);
          $set_feedback = $test->getRunner()->setKeyValuePair($test->getSuite()
            ->variables(), $data['name'], $data['value']);
          $test->addMessage(new Message(
            [$set_feedback],
            MessageType::DEBUG,
            Verbosity::DEBUG
          ));
        },
      ],
    ];
  }

  public static function getId(): string {
    return 'value';
  }

  private static function getAssertTypeMap(): array {
    // @see schema.definitions.json
    return [
      'is' => Assert::ASSERT_EQUALS,
      'is not' => Assert::ASSERT_NOT_EQUALS,
      'contains' => Assert::ASSERT_CONTAINS,
      'not contains' => Assert::ASSERT_NOT_CONTAINS,
      'matches' => Assert::ASSERT_MATCHES,
      'not matches' => Assert::ASSERT_NOT_MATCHES,
    ];
  }

  /**
   * @param \AKlump\CheckPages\Interfaces\HasConfigInterface $context
   *
   * @return bool
   *   True if $context is purely a set event, that is the value is not to be
   *   searched or analyzed.
   *   keys: value and set.
   */
  private function isOnlySet(HasConfigInterface $context): bool {
    $config = $context->getConfig();
    $assert_type = array_intersect_key(self::getAssertTypeMap(), $config);

    return $context->has(Assert::ASSERT_SETTER) && $context->has('value') && empty($assert_type);
  }

  private function createAssertFromTest(Test $test): Assert {
    $test = clone $test;
    $config = $test->getConfig();
    $test->interpolate($config);
    $test->setConfig($config);

    $assert_type = array_intersect_key(self::getAssertTypeMap(), $config);
    if (!$assert_type) {
      throw new \InvalidArgumentException('$test does not contain an assertable configuration');
    }

    $assert_type = key($assert_type);
    $assert = new Assert(self::getId(), [
      $assert_type => $test->get('value'),
    ], $test);
    $assert->setHaystack([$config['value']]);

    $expected_value = $test->get($assert_type);;
    $assert->setAssertion(self::getAssertTypeMap()[$assert_type], $expected_value);

    return $assert;
  }

  private function handleSetAndValueAtTestScope(Test $test) {
    $config = $test->getConfig();
    $handler = new self();
    $set_message = $handler->setKeyValuePair(
      $test->getSuite()->variables(),
      $config[Assert::ASSERT_SETTER],
      $config['value']
    );
    $test->addMessage(new Message([$set_message], MessageType::DEBUG, Verbosity::DEBUG));
  }

  private function handleInterpolation(Test $test) {
    $suite = $test->getSuite();

    $config = $test->getConfig();
    $suite->interpolate($config['value']);
    $suite->interpolate($config[Assert::ASSERT_SETTER]);

    $test->setConfig($config);
    $test->getSuite()
      ->variables()
      ->setItem($config['value'], $config[Assert::ASSERT_SETTER]);
  }
}
